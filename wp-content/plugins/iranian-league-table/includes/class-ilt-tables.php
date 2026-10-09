<?php
/**
 * Saved tables: named option sets referenced as [iran_league id="N"].
 *
 * @package Iranian_League_Table
 */

defined( 'ABSPATH' ) || exit;

/**
 * Private post type storing sanitized display options as JSON post meta.
 */
final class ILT_Tables {

	const POST_TYPE   = 'ilt_table';
	const META_KEY    = '_ilt_options';
	const USAGE_CACHE = 'ilt_table_usage';

	/**
	 * Hooks.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_post_type' ) );
		add_filter( 'ilt_shortcode_base_values', array( __CLASS__, 'shortcode_base_values' ), 10, 2 );
		add_action( 'save_post', array( __CLASS__, 'flush_usage' ) );
		add_action( 'deleted_post', array( __CLASS__, 'flush_usage' ) );
	}

	/**
	 * Registers the post type (no UI of its own; managed from the plugin's admin pages).
	 */
	public static function register_post_type() {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'          => array(
					'name'          => __( 'Saved Tables', 'iranian-league-table' ),
					'singular_name' => __( 'Saved Table', 'iranian-league-table' ),
				),
				'public'          => false,
				'show_ui'         => false,
				'show_in_rest'    => false,
				'rewrite'         => false,
				'query_var'       => false,
				'supports'        => array( 'title' ),
				'capability_type' => 'post',
				'map_meta_cap'    => true,
			)
		);
	}

	/**
	 * A saved table.
	 *
	 * @param int $id Post id.
	 * @return array{id:int, title:string, values:array}|null
	 */
	public static function get( $id ) {
		$post = get_post( absint( $id ) );
		if ( ! $post || self::POST_TYPE !== $post->post_type || 'publish' !== $post->post_status ) {
			return null;
		}
		$raw = json_decode( (string) get_post_meta( $post->ID, self::META_KEY, true ), true );
		return array(
			'id'     => (int) $post->ID,
			'title'  => (string) $post->post_title,
			'values' => ILT_Attributes::sanitize( is_array( $raw ) ? $raw : array() ),
		);
	}

	/**
	 * Creates or updates a saved table.
	 *
	 * @param string $title  Name.
	 * @param array  $values Raw or normalized values.
	 * @param int    $id     Existing id to update, or 0.
	 * @return int|WP_Error Post id.
	 */
	public static function save( $title, $values, $id = 0 ) {
		$title  = sanitize_text_field( $title );
		$values = ILT_Attributes::sanitize( $values );
		if ( '' === $title ) {
			$title = ILT_Leagues::label( $values['league'] );
		}
		$post = array(
			'post_type'   => self::POST_TYPE,
			'post_status' => 'publish',
			'post_title'  => $title,
		);
		if ( $id ) {
			if ( null === self::get( $id ) ) {
				return new WP_Error( 'ilt_not_found', __( 'Saved table not found.', 'iranian-league-table' ), array( 'status' => 404 ) );
			}
			$post['ID'] = absint( $id );
			$result     = wp_update_post( wp_slash( $post ), true );
		} else {
			$result = wp_insert_post( wp_slash( $post ), true );
		}
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		update_post_meta( $result, self::META_KEY, wp_slash( wp_json_encode( $values ) ) );
		return (int) $result;
	}

	/**
	 * Copies a saved table.
	 *
	 * @param int $id Source id.
	 * @return int|WP_Error New id.
	 */
	public static function duplicate( $id ) {
		$table = self::get( $id );
		if ( null === $table ) {
			return new WP_Error( 'ilt_not_found', __( 'Saved table not found.', 'iranian-league-table' ), array( 'status' => 404 ) );
		}
		/* translators: %s: saved table name. */
		return self::save( sprintf( __( '%s (copy)', 'iranian-league-table' ), $table['title'] ), $table['values'] );
	}

	/**
	 * Deletes a saved table permanently.
	 *
	 * @param int $id Post id.
	 * @return bool
	 */
	public static function delete( $id ) {
		if ( null === self::get( $id ) ) {
			return false;
		}
		return (bool) wp_delete_post( absint( $id ), true );
	}

	/**
	 * All saved tables, newest first.
	 *
	 * @return array<int, array{id:int, title:string, values:array}>
	 */
	public static function all() {
		$ids = get_posts(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => 500, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page -- Small private list.
				'orderby'        => 'date',
				'order'          => 'DESC',
				'fields'         => 'ids',
			)
		);
		$out = array();
		foreach ( $ids as $id ) {
			$table = self::get( $id );
			if ( $table ) {
				$out[] = $table;
			}
		}
		return $out;
	}

	/**
	 * Shortcode filter: `id="N"` starts from the saved table's values.
	 *
	 * @param array $values Base values.
	 * @param array $atts   Raw shortcode attributes.
	 * @return array
	 */
	public static function shortcode_base_values( $values, $atts ) {
		if ( empty( $atts['id'] ) ) {
			return $values;
		}
		$table = self::get( $atts['id'] );
		return $table ? $table['values'] : $values;
	}

	/**
	 * How many posts use each saved table (cached for an hour; flushed when posts change).
	 *
	 * @return array<int, int> Table id => number of posts.
	 */
	public static function usage() {
		$cached = get_transient( self::USAGE_CACHE );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Cached in a transient below.
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT ID, post_content FROM {$wpdb->posts} WHERE post_status IN ('publish','future','draft','pending','private') AND post_type NOT IN ('revision', %s) AND post_content LIKE %s",
				self::POST_TYPE,
				'%' . $wpdb->esc_like( '[' . ILT_Attributes::SHORTCODE ) . '%'
			)
		);

		$usage = array();
		$regex = '/' . get_shortcode_regex( array( ILT_Attributes::SHORTCODE ) ) . '/';
		foreach ( (array) $rows as $row ) {
			if ( ! preg_match_all( $regex, $row->post_content, $matches, PREG_SET_ORDER ) ) {
				continue;
			}
			$ids = array();
			foreach ( $matches as $m ) {
				$atts = shortcode_parse_atts( $m[3] );
				if ( is_array( $atts ) && ! empty( $atts['id'] ) ) {
					$ids[ absint( $atts['id'] ) ] = true;
				}
			}
			foreach ( array_keys( $ids ) as $id ) {
				$usage[ $id ] = ( isset( $usage[ $id ] ) ? $usage[ $id ] : 0 ) + 1;
			}
		}
		set_transient( self::USAGE_CACHE, $usage, HOUR_IN_SECONDS );
		return $usage;
	}

	/**
	 * Clears the usage cache.
	 */
	public static function flush_usage() {
		delete_transient( self::USAGE_CACHE );
	}
}
