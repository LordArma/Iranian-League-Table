<?php
/**
 * Admin menu, assets and form handlers.
 *
 * @package Iranian_League_Table
 */

defined( 'ABSPATH' ) || exit;

/**
 * "League Table" admin menu: Shortcode Builder, Saved Tables, Settings, Help.
 */
final class ILT_Admin {

	const MENU_SLUG     = 'ilt-builder';
	const TABLES_SLUG   = 'ilt-tables';
	const SETTINGS_SLUG = 'ilt-settings';
	const HELP_SLUG     = 'ilt-help';

	/**
	 * Page hook suffixes.
	 *
	 * @var array<string, string>
	 */
	private static $hooks = array();

	/**
	 * Hooks.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
		add_filter( 'admin_body_class', array( __CLASS__, 'body_class' ) );
		add_action( 'admin_init', array( 'ILT_Settings_Page', 'register' ) );
		add_action( 'admin_post_ilt_refresh_league', array( 'ILT_Settings_Page', 'handle_refresh' ) );
		add_action( 'admin_post_ilt_clear_logos', array( 'ILT_Settings_Page', 'handle_clear_logos' ) );
		add_action( 'admin_post_ilt_table_action', array( 'ILT_Tables_List', 'handle_action' ) );
		add_action( 'media_buttons', array( __CLASS__, 'media_button' ) );
		add_action( 'customize_controls_enqueue_scripts', array( __CLASS__, 'enqueue_widget_assets' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( ILT_PLUGIN_FILE ), array( __CLASS__, 'action_links' ) );
	}

	/**
	 * Registers the menu.
	 */
	public static function register_menu() {
		self::$hooks['builder'] = add_menu_page(
			__( 'Shortcode Builder', 'iranian-league-table' ),
			__( 'League Table', 'iranian-league-table' ),
			'edit_posts',
			self::MENU_SLUG,
			array( 'ILT_Builder_Page', 'render' ),
			'dashicons-editor-table',
			58
		);
		add_submenu_page( self::MENU_SLUG, __( 'Shortcode Builder', 'iranian-league-table' ), __( 'Shortcode Builder', 'iranian-league-table' ), 'edit_posts', self::MENU_SLUG, array( 'ILT_Builder_Page', 'render' ) );
		self::$hooks['tables']   = add_submenu_page( self::MENU_SLUG, __( 'Saved Tables', 'iranian-league-table' ), __( 'Saved Tables', 'iranian-league-table' ), 'edit_posts', self::TABLES_SLUG, array( 'ILT_Tables_List', 'render_page' ) );
		self::$hooks['settings'] = add_submenu_page( self::MENU_SLUG, __( 'League Table Settings', 'iranian-league-table' ), __( 'Settings', 'iranian-league-table' ), 'manage_options', self::SETTINGS_SLUG, array( 'ILT_Settings_Page', 'render' ) );
		self::$hooks['help']     = add_submenu_page( self::MENU_SLUG, __( 'League Table Help', 'iranian-league-table' ), __( 'Help', 'iranian-league-table' ), 'edit_posts', self::HELP_SLUG, array( __CLASS__, 'render_help' ) );
	}

	/**
	 * URL of a plugin admin page.
	 *
	 * @param string $slug Page slug.
	 * @param array  $args Extra query args.
	 * @return string
	 */
	public static function url( $slug, $args = array() ) {
		return add_query_arg( array_merge( array( 'page' => $slug ), $args ), admin_url( 'admin.php' ) );
	}

	/**
	 * Whether the Builder is shown inside the classic editor's modal.
	 *
	 * @return bool
	 */
	public static function is_modal() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display flag only.
		return isset( $_GET['ilt_modal'] ) && '1' === $_GET['ilt_modal'];
	}

	/**
	 * Adds a body class for the modal Builder.
	 *
	 * @param string $classes Classes.
	 * @return string
	 */
	public static function body_class( $classes ) {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( self::is_modal() && $screen && isset( self::$hooks['builder'] ) && self::$hooks['builder'] === $screen->id ) {
			$classes .= ' ilt-modal';
		}
		return $classes;
	}

	/**
	 * Enqueues assets on the plugin's pages, and Thickbox on classic editor screens.
	 *
	 * @param string $hook Current page hook.
	 */
	public static function enqueue( $hook ) {
		if ( in_array( $hook, array( 'post.php', 'post-new.php' ), true ) && current_user_can( 'edit_posts' ) ) {
			add_thickbox();
		}
		if ( 'widgets.php' === $hook ) {
			self::enqueue_widget_assets();
		}
		if ( ! in_array( $hook, self::$hooks, true ) ) {
			return;
		}

		wp_enqueue_style( 'ilt-admin', plugins_url( 'assets/css/admin.css', ILT_PLUGIN_FILE ), array( 'wp-color-picker' ), ILT_VERSION );

		if ( self::$hooks['builder'] === $hook ) {
			wp_enqueue_script( 'ilt-builder', plugins_url( 'assets/js/builder.js', ILT_PLUGIN_FILE ), array( 'jquery', 'wp-color-picker' ), ILT_VERSION, true );
			wp_add_inline_script( 'ilt-builder', 'window.iltBuilder = ' . wp_json_encode( ILT_Builder_Page::script_data() ) . ';', 'before' );
		}
		if ( self::$hooks['tables'] === $hook ) {
			wp_enqueue_script( 'ilt-tables', plugins_url( 'assets/js/tables.js', ILT_PLUGIN_FILE ), array(), ILT_VERSION, true );
			wp_add_inline_script(
				'ilt-tables',
				'window.iltTables = ' . wp_json_encode(
					array(
						'copied'        => __( 'Copied!', 'iranian-league-table' ),
						'copyFailed'    => __( 'Copy failed. Select the shortcode and copy it manually.', 'iranian-league-table' ),
						'confirmDelete' => __( 'Delete this saved table? Pages that use its shortcode will show the default table instead.', 'iranian-league-table' ),
					)
				) . ';',
				'before'
			);
		}
	}

	/**
	 * Color pickers and presets for the legacy widget form (Widgets screen and Customizer).
	 */
	public static function enqueue_widget_assets() {
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'ilt-widget', plugins_url( 'assets/js/widget.js', ILT_PLUGIN_FILE ), array( 'jquery', 'wp-color-picker' ), ILT_VERSION, true );
	}

	/**
	 * "Insert League Table" button above the classic editor.
	 *
	 * @param string $editor_id Editor id.
	 */
	public static function media_button( $editor_id = 'content' ) {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return;
		}
		$url = add_query_arg(
			array(
				'ilt_modal' => '1',
				'editor'    => $editor_id,
				'TB_iframe' => 'true',
				'width'     => '1100',
				'height'    => '800',
			),
			self::url( self::MENU_SLUG )
		);
		printf(
			'<a href="%1$s" class="button thickbox ilt-insert-button" title="%2$s"><span class="dashicons dashicons-editor-table" style="vertical-align:text-top;" aria-hidden="true"></span> %3$s</a>',
			esc_url( $url ),
			esc_attr__( 'Insert League Table', 'iranian-league-table' ),
			esc_html__( 'League Table', 'iranian-league-table' )
		);
	}

	/**
	 * Links on the Plugins screen.
	 *
	 * @param array $links Links.
	 * @return array
	 */
	public static function action_links( $links ) {
		array_unshift( $links, '<a href="' . esc_url( self::url( self::MENU_SLUG ) ) . '">' . esc_html__( 'Shortcode Builder', 'iranian-league-table' ) . '</a>' );
		return $links;
	}

	/**
	 * Shows a notice passed back from a form handler (?ilt_notice=...).
	 */
	public static function render_notice() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Display only; values are whitelisted.
		$code = isset( $_GET['ilt_notice'] ) ? sanitize_key( wp_unslash( $_GET['ilt_notice'] ) ) : '';
		// phpcs:enable
		$messages = array(
			'refreshed'      => array( 'success', __( 'League data refreshed.', 'iranian-league-table' ) ),
			'refresh_failed' => array( 'error', __( 'Could not refresh the league data. See "Last error" below.', 'iranian-league-table' ) ),
			'logos_cleared'  => array( 'success', __( 'Logo cache cleared. Logos will be downloaded again in the background.', 'iranian-league-table' ) ),
			'duplicated'     => array( 'success', __( 'Saved table duplicated.', 'iranian-league-table' ) ),
			'deleted'        => array( 'success', __( 'Saved table deleted.', 'iranian-league-table' ) ),
			'failed'         => array( 'error', __( 'The action could not be completed.', 'iranian-league-table' ) ),
		);
		if ( isset( $messages[ $code ] ) ) {
			printf( '<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>', esc_attr( $messages[ $code ][0] ), esc_html( $messages[ $code ][1] ) );
		}
	}

	/**
	 * Help page.
	 */
	public static function render_help() {
		$examples = array(
			array( __( 'Basic display of the Iranian Premier League (Persian Gulf) table', 'iranian-league-table' ), '[iran_league league="persiangulf" mode="basic"]' ),
			array( __( 'Advanced display of the Iranian League One (Azadegan) table', 'iranian-league-table' ), '[iran_league league="azadegan" mode="advanced"]' ),
			array( __( 'Basic display of the Iranian Premier League table without team logos', 'iranian-league-table' ), '[iran_league league="persiangulf" mode="basic" logo="false"]' ),
			array( __( 'Basic display of the Iranian Premier League table without team logos and Farsi numbers', 'iranian-league-table' ), '[iran_league league="persiangulf" mode="basic" logo="false" farsi_numbers="true"]' ),
			array( __( 'Basic display of the Iranian Women\'s League (Kowsar) without logos', 'iranian-league-table' ), '[iran_league league="kowsar" mode="basic" logo="false"]' ),
			array( __( 'Display the Iranian Premier League table with the desired color and size', 'iranian-league-table' ), '[iran_league league="bartar" mode="advanced" title_backcolor="#212121" title_color="#ffffff" text_color="#212121" odd_color="#ffffff" even_color="#eeeeee" logo_size="15" logo="true" title_font="12" text_font="13" farsi_numbers="false"]' ),
			array( __( 'A saved table, with one option overridden', 'iranian-league-table' ), '[iran_league id="12" mode="basic"]' ),
		);
		?>
		<div class="wrap ilt-admin">
			<h1><?php esc_html_e( 'League Table Help', 'iranian-league-table' ); ?></h1>
			<p>
				<?php esc_html_e( 'The easiest way to make a table is the Shortcode Builder: pick the options, check the preview, and copy the shortcode into any post, page or widget.', 'iranian-league-table' ); ?>
				<a class="button button-primary" href="<?php echo esc_url( self::url( self::MENU_SLUG ) ); ?>"><?php esc_html_e( 'Open the Shortcode Builder', 'iranian-league-table' ); ?></a>
			</p>

			<h2><?php esc_html_e( 'Example of Using Shortcode', 'iranian-league-table' ); ?></h2>
			<?php foreach ( $examples as $example ) : ?>
				<p><?php echo esc_html( $example[0] ); ?></p>
				<p><code dir="ltr"><?php echo esc_html( $example[1] ); ?></code></p>
			<?php endforeach; ?>

			<h2><?php esc_html_e( 'Attributes', 'iranian-league-table' ); ?></h2>
			<table class="widefat striped ilt-help-attributes">
				<thead>
					<tr>
						<th scope="col"><?php esc_html_e( 'Attribute', 'iranian-league-table' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Description', 'iranian-league-table' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Default', 'iranian-league-table' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td><code>id</code></td>
						<td><?php esc_html_e( 'Saved table to use. Other attributes override its options.', 'iranian-league-table' ); ?></td>
						<td>—</td>
					</tr>
					<?php foreach ( ILT_Attributes::schema() as $key => $field ) : ?>
						<tr>
							<td><code><?php echo esc_html( $key ); ?></code></td>
							<td>
								<?php echo esc_html( $field['label'] ); ?>
								<?php if ( 'league' === $field['type'] ) : ?>
									<br><small dir="ltr"><?php echo esc_html( implode( ', ', array_keys( $field['choices'] ) ) ); ?></small>
								<?php elseif ( 'enum' === $field['type'] ) : ?>
									<br><small dir="ltr"><?php echo esc_html( implode( ', ', array_keys( $field['choices'] ) ) ); ?></small>
								<?php elseif ( 'int' === $field['type'] ) : ?>
									<br><small dir="ltr"><?php echo esc_html( $field['min'] . '–' . $field['max'] ); ?></small>
								<?php elseif ( 'bool' === $field['type'] ) : ?>
									<br><small dir="ltr">true, false</small>
								<?php endif; ?>
							</td>
							<td><code dir="ltr"><?php echo esc_html( ILT_Attributes::to_string( $field['default'] ) ); ?></code></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<h2><?php esc_html_e( 'Customizing the look in a theme', 'iranian-league-table' ); ?></h2>
			<p><?php esc_html_e( 'Colors and sizes are CSS custom properties on the table wrapper, so a theme can override them:', 'iranian-league-table' ); ?></p>
			<pre dir="ltr" class="ilt-code">.ilt { --ilt-head-bg: #003366; --ilt-head-fg: #fff; --ilt-odd: #fff; --ilt-even: #f2f6fa; }</pre>
			<p><?php esc_html_e( 'To change the markup, copy templates/table.php from the plugin folder to iranian-league-table/table.php inside your theme.', 'iranian-league-table' ); ?></p>
		</div>
		<?php
	}
}
