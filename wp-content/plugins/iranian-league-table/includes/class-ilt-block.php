<?php
/**
 * "League Table" block.
 *
 * @package Iranian_League_Table
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers the iranian-league-table/standings block. Rendering is server-side with the same
 * renderer as the shortcode; the editor controls are generated from the attribute schema.
 */
final class ILT_Block {

	const NAME = 'iranian-league-table/standings';

	/**
	 * Hooks.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ), 20 );
	}

	/**
	 * Registers the block from build/standings/block.json.
	 */
	public static function register() {
		$dir = ILT_PLUGIN_DIR . 'build/standings';
		if ( ! file_exists( $dir . '/block.json' ) ) {
			return;
		}
		register_block_type(
			$dir,
			array(
				'render_callback' => array( __CLASS__, 'render' ),
			)
		);

		$handle = generate_block_asset_handle( self::NAME, 'editorScript' );
		add_action(
			'enqueue_block_editor_assets',
			function () use ( $handle ) {
				wp_add_inline_script( $handle, 'window.iltBlock = ' . wp_json_encode( self::editor_data() ) . ';', 'before' );
			}
		);
	}

	/**
	 * Server-side render.
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	public static function render( $attributes ) {
		$values = self::values( $attributes );
		$html   = ILT_Renderer::render_league( $values );
		$attrs  = function_exists( 'get_block_wrapper_attributes' ) ? get_block_wrapper_attributes() : 'class="wp-block-iranian-league-table-standings"';
		return '<div ' . $attrs . '>' . $html . '</div>';
	}

	/**
	 * Normalized values for block attributes: saved table (if any), then the block's own options.
	 *
	 * @param array $attributes Block attributes.
	 * @return array
	 */
	public static function values( $attributes ) {
		$options = isset( $attributes['options'] ) && is_array( $attributes['options'] ) ? $attributes['options'] : array();
		$table   = ! empty( $attributes['tableId'] ) ? ILT_Tables::get( (int) $attributes['tableId'] ) : null;
		return ILT_Attributes::sanitize( $options, $table ? $table['values'] : null );
	}

	/**
	 * Data for the editor script.
	 *
	 * @return array
	 */
	public static function editor_data() {
		$tables = array();
		if ( current_user_can( 'edit_posts' ) ) {
			foreach ( ILT_Tables::all() as $table ) {
				$tables[] = $table;
			}
		}
		$aliases = array();
		foreach ( ILT_Leagues::all() as $slug => $league ) {
			foreach ( (array) $league['aliases'] as $alias ) {
				$aliases[ $alias ] = $slug;
			}
			$aliases[ $slug ] = $slug;
		}
		return array(
			'schema'     => ILT_Attributes::for_js(),
			'defaults'   => ILT_Attributes::defaults(),
			'presets'    => ILT_Presets::all(),
			'tables'     => $tables,
			'aliases'    => $aliases,
			'builderUrl' => admin_url( 'admin.php?page=ilt-builder' ),
			'i18n'       => array(
				'savedTable'     => __( 'Saved table', 'iranian-league-table' ),
				'noTable'        => __( '— None (use the options below) —', 'iranian-league-table' ),
				'savedTableHelp' => __( 'Start from a saved table. Options you change below override it for this block only.', 'iranian-league-table' ),
				'manage'         => __( 'Manage saved tables', 'iranian-league-table' ),
				'content'        => __( 'Content', 'iranian-league-table' ),
				'zones'          => __( 'Zones', 'iranian-league-table' ),
				'colors'         => __( 'Colors', 'iranian-league-table' ),
				'sizes'          => __( 'Typography & sizes', 'iranian-league-table' ),
				'preset'         => __( 'Theme preset', 'iranian-league-table' ),
				'choosePreset'   => __( '— Choose a preset —', 'iranian-league-table' ),
				'reset'          => __( 'Reset to defaults', 'iranian-league-table' ),
			),
		);
	}
}
