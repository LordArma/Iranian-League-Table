<?php
/**
 * Plugin wiring.
 *
 * @package Iranian_League_Table
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers hooks, cron events and upgrade routines.
 */
final class ILT_Plugin {

	const VERSION_OPTION = 'ilt_version';
	const CRON_SCHEDULE  = 'ilt_cache_ttl';

	/**
	 * Hooks everything up. Called once from the bootstrap file.
	 */
	public static function init() {
		add_action( 'plugins_loaded', array( __CLASS__, 'load_textdomain' ) );
		add_action( 'init', array( __CLASS__, 'on_init' ) );
		add_action( 'widgets_init', array( __CLASS__, 'register_widget' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_early' ) );

		add_filter( 'cron_schedules', array( __CLASS__, 'cron_schedules' ) ); // phpcs:ignore WordPress.WP.CronInterval.ChangeDetected -- Interval is the configurable cache TTL (min 60s).
		add_action( ILT_Api::REFRESH_HOOK, array( 'ILT_Api', 'refresh' ) );
		add_action( ILT_Api::REFRESH_ALL_HOOK, array( 'ILT_Api', 'refresh_all' ) );
		add_action( ILT_Logos::SYNC_HOOK, array( 'ILT_Logos', 'sync' ) );

		ILT_Shortcode::register();

		register_activation_hook( ILT_PLUGIN_FILE, array( __CLASS__, 'activate' ) );
		register_deactivation_hook( ILT_PLUGIN_FILE, array( __CLASS__, 'deactivate' ) );
	}

	/**
	 * Loads translations.
	 */
	public static function load_textdomain() {
		load_plugin_textdomain( 'iranian-league-table', false, dirname( plugin_basename( ILT_PLUGIN_FILE ) ) . '/languages/' );
	}

	/**
	 * Runs on init: assets, upgrades, cron.
	 */
	public static function on_init() {
		ILT_Renderer::register_style();
		self::maybe_upgrade();
		self::schedule_cron();
	}

	/**
	 * Registers the legacy widget.
	 */
	public static function register_widget() {
		register_widget( 'Iranian_League_Widget' );
	}

	/**
	 * Enqueues the stylesheet in <head> when we can tell a table will be shown, avoiding a flash of
	 * unstyled content. Other cases (e.g. shortcodes in page builders) get it in the footer.
	 */
	public static function enqueue_early() {
		$post = get_post();
		if ( ( $post && has_shortcode( (string) $post->post_content, ILT_Attributes::SHORTCODE ) )
			|| is_active_widget( false, false, 'iranianleaguetable_widget', true ) ) {
			ILT_Renderer::enqueue_style();
		}
	}

	/**
	 * Adds a cron interval equal to the cache TTL.
	 *
	 * @param array $schedules Schedules.
	 * @return array
	 */
	public static function cron_schedules( $schedules ) {
		$schedules[ self::CRON_SCHEDULE ] = array(
			'interval' => ILT_Api::ttl(),
			'display'  => __( 'League table cache lifetime', 'iranian-league-table' ),
		);
		return $schedules;
	}

	/**
	 * Makes sure the recurring refresh event exists.
	 */
	public static function schedule_cron() {
		if ( ! wp_next_scheduled( ILT_Api::REFRESH_ALL_HOOK ) ) {
			wp_schedule_event( time() + MINUTE_IN_SECONDS, self::CRON_SCHEDULE, ILT_Api::REFRESH_ALL_HOOK );
		}
	}

	/**
	 * Re-creates the recurring event, e.g. after the TTL changes.
	 */
	public static function reschedule_cron() {
		wp_clear_scheduled_hook( ILT_Api::REFRESH_ALL_HOOK );
		self::schedule_cron();
	}

	/**
	 * Activation.
	 */
	public static function activate() {
		self::maybe_upgrade();
		self::schedule_cron();
	}

	/**
	 * Deactivation: stop background work; data is kept until uninstall.
	 */
	public static function deactivate() {
		wp_clear_scheduled_hook( ILT_Api::REFRESH_ALL_HOOK );
		wp_unschedule_hook( ILT_Api::REFRESH_HOOK );
		wp_unschedule_hook( ILT_Logos::SYNC_HOOK );
	}

	/**
	 * One-off migrations when the stored version is older than the code.
	 */
	public static function maybe_upgrade() {
		$stored = (string) get_option( self::VERSION_OPTION, '0' );
		if ( version_compare( $stored, ILT_VERSION, '>=' ) ) {
			return;
		}

		if ( version_compare( $stored, '4.0.0', '<' ) ) {
			// 3.x kept its cache in the plugin folder and two global timestamps.
			delete_option( 'ilt_last_update' );
			delete_option( 'ilt_last_logo' );
			$old = glob( ILT_PLUGIN_DIR . 'data/*' );
			foreach ( is_array( $old ) ? $old : array() as $file ) {
				if ( is_file( $file ) && 'index.php' !== basename( $file ) ) {
					wp_delete_file( $file );
				}
			}
			if ( is_dir( ILT_PLUGIN_DIR . 'data' ) ) {
				wp_delete_file( ILT_PLUGIN_DIR . 'data/index.php' );
				@rmdir( ILT_PLUGIN_DIR . 'data' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_system_operations_rmdir -- Best effort.
			}
		}

		update_option( self::VERSION_OPTION, ILT_VERSION );
	}
}
