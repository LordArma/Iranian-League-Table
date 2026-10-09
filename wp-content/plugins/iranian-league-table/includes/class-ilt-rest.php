<?php
/**
 * REST endpoints used by the Shortcode Builder.
 *
 * @package Iranian_League_Table
 */

defined( 'ABSPATH' ) || exit;

/**
 * Routes under /wp-json/ilt/v1. Cookie-authenticated requests need the `wp_rest` nonce
 * (X-WP-Nonce), which WordPress core enforces before permission callbacks run.
 */
final class ILT_Rest {

	const NAMESPACE_V1 = 'ilt/v1';

	/**
	 * Hooks.
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	/**
	 * Registers routes.
	 */
	public static function register_routes() {
		$values_arg = array(
			'values' => array(
				'type'     => 'object',
				'required' => true,
			),
		);

		register_rest_route(
			self::NAMESPACE_V1,
			'/preview',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'preview' ),
				'permission_callback' => array( __CLASS__, 'can_edit_posts' ),
				'args'                => $values_arg,
			)
		);

		register_rest_route(
			self::NAMESPACE_V1,
			'/parse',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'parse' ),
				'permission_callback' => array( __CLASS__, 'can_edit_posts' ),
				'args'                => array(
					'shortcode' => array(
						'type'     => 'string',
						'required' => true,
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE_V1,
			'/tables',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'create_table' ),
				'permission_callback' => array( __CLASS__, 'can_edit_posts' ),
				'args'                => $values_arg + array(
					'title' => array(
						'type'    => 'string',
						'default' => '',
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE_V1,
			'/tables/(?P<id>\d+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( __CLASS__, 'get_table' ),
					'permission_callback' => array( __CLASS__, 'can_edit_table' ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( __CLASS__, 'update_table' ),
					'permission_callback' => array( __CLASS__, 'can_edit_table' ),
					'args'                => $values_arg + array(
						'title' => array(
							'type'    => 'string',
							'default' => '',
						),
					),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( __CLASS__, 'delete_table' ),
					'permission_callback' => array( __CLASS__, 'can_delete_table' ),
				),
			)
		);
	}

	/**
	 * Permission: may build tables.
	 *
	 * @return bool
	 */
	public static function can_edit_posts() {
		return current_user_can( 'edit_posts' );
	}

	/**
	 * Permission: may edit this saved table.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return bool
	 */
	public static function can_edit_table( $request ) {
		return current_user_can( 'edit_post', (int) $request['id'] );
	}

	/**
	 * Permission: may delete this saved table.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return bool
	 */
	public static function can_delete_table( $request ) {
		return current_user_can( 'delete_post', (int) $request['id'] );
	}

	/**
	 * Renders the table with the same renderer as the front end.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function preview( $request ) {
		$values = ILT_Attributes::sanitize( (array) $request['values'] );
		return rest_ensure_response(
			array(
				'html'      => ILT_Renderer::render_league( $values ),
				'values'    => $values,
				'shortcode' => ILT_Attributes::to_shortcode( $values ),
			)
		);
	}

	/**
	 * Parses a pasted shortcode into Builder values.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function parse( $request ) {
		$text = (string) $request['shortcode'];
		if ( ! preg_match( '/' . get_shortcode_regex( array( ILT_Attributes::SHORTCODE ) ) . '/', $text, $m ) ) {
			return new WP_Error( 'ilt_no_shortcode', __( 'No [iran_league] shortcode found in the pasted text.', 'iranian-league-table' ), array( 'status' => 400 ) );
		}
		$atts  = shortcode_parse_atts( $m[3] );
		$atts  = is_array( $atts ) ? array_change_key_case( $atts, CASE_LOWER ) : array();
		$table = ! empty( $atts['id'] ) ? ILT_Tables::get( $atts['id'] ) : null;
		$base  = $table ? $table['values'] : null;
		return rest_ensure_response(
			array(
				'values' => ILT_Attributes::sanitize( $atts, $base ),
				'table'  => $table && current_user_can( 'edit_post', $table['id'] ) ? array(
					'id'    => $table['id'],
					'title' => $table['title'],
				) : null,
			)
		);
	}

	/**
	 * Returns a saved table.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function get_table( $request ) {
		$table = ILT_Tables::get( (int) $request['id'] );
		if ( null === $table ) {
			return new WP_Error( 'ilt_not_found', __( 'Saved table not found.', 'iranian-league-table' ), array( 'status' => 404 ) );
		}
		return rest_ensure_response( self::table_response( $table ) );
	}

	/**
	 * Saves a new table.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function create_table( $request ) {
		$id = ILT_Tables::save( (string) $request['title'], (array) $request['values'] );
		if ( is_wp_error( $id ) ) {
			return $id;
		}
		$response = rest_ensure_response( self::table_response( ILT_Tables::get( $id ) ) );
		$response->set_status( 201 );
		return $response;
	}

	/**
	 * Updates a saved table.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function update_table( $request ) {
		$id = ILT_Tables::save( (string) $request['title'], (array) $request['values'], (int) $request['id'] );
		if ( is_wp_error( $id ) ) {
			return $id;
		}
		return rest_ensure_response( self::table_response( ILT_Tables::get( $id ) ) );
	}

	/**
	 * Deletes a saved table.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function delete_table( $request ) {
		if ( ! ILT_Tables::delete( (int) $request['id'] ) ) {
			return new WP_Error( 'ilt_not_found', __( 'Saved table not found.', 'iranian-league-table' ), array( 'status' => 404 ) );
		}
		return rest_ensure_response( array( 'deleted' => true ) );
	}

	/**
	 * Response shape for a saved table.
	 *
	 * @param array $table Saved table.
	 * @return array
	 */
	private static function table_response( $table ) {
		return array(
			'id'        => $table['id'],
			'title'     => $table['title'],
			'values'    => $table['values'],
			'shortcode' => ILT_Attributes::to_shortcode( $table['values'], $table['id'], $table['values'] ),
		);
	}
}
