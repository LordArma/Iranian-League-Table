<?php
/**
 * Settings page: cache lifetime, manual refresh and data status.
 *
 * @package Iranian_League_Table
 */

defined( 'ABSPATH' ) || exit;

/**
 * Settings and diagnostics.
 */
final class ILT_Settings_Page {

	const GROUP = 'ilt_settings_group';

	/**
	 * Registers the setting.
	 */
	public static function register() {
		register_setting(
			self::GROUP,
			ILT_Api::SETTINGS_OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
				'default'           => array( 'cache_ttl' => ILT_Api::DEFAULT_TTL ),
			)
		);
		add_action( 'update_option_' . ILT_Api::SETTINGS_OPTION, array( 'ILT_Plugin', 'reschedule_cron' ) );
	}

	/**
	 * Sanitizes settings. The form uses minutes; the option stores seconds.
	 *
	 * @param mixed $input Raw input.
	 * @return array
	 */
	public static function sanitize( $input ) {
		$input   = is_array( $input ) ? $input : array();
		$minutes = isset( $input['cache_ttl_minutes'] ) ? absint( $input['cache_ttl_minutes'] ) : (int) ( ILT_Api::DEFAULT_TTL / MINUTE_IN_SECONDS );
		if ( isset( $input['cache_ttl'] ) && ! isset( $input['cache_ttl_minutes'] ) ) {
			$minutes = (int) ceil( absint( $input['cache_ttl'] ) / MINUTE_IN_SECONDS );
		}
		return array(
			'cache_ttl'       => max( 1, min( 1440, $minutes ) ) * MINUTE_IN_SECONDS,
			'structured_data' => ! empty( $input['structured_data'] ),
		);
	}

	/**
	 * Renders the page.
	 */
	public static function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$minutes = (int) round( ILT_Api::ttl() / MINUTE_IN_SECONDS );
		$used    = ILT_Api::used_leagues();
		?>
		<div class="wrap ilt-admin">
			<h1><?php esc_html_e( 'League Table Settings', 'iranian-league-table' ); ?></h1>
			<?php ILT_Admin::render_notice(); ?>

			<form method="post" action="options.php">
				<?php settings_fields( self::GROUP ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="ilt-cache-ttl"><?php esc_html_e( 'Refresh data every', 'iranian-league-table' ); ?></label></th>
						<td>
							<input type="number" min="1" max="1440" step="1" class="small-text" id="ilt-cache-ttl" name="<?php echo esc_attr( ILT_Api::SETTINGS_OPTION ); ?>[cache_ttl_minutes]" value="<?php echo esc_attr( $minutes ); ?>">
							<?php esc_html_e( 'minutes', 'iranian-league-table' ); ?>
							<p class="description"><?php esc_html_e( 'Standings are refreshed in the background, so visitors never wait for Varzesh3. Shorter times show results sooner but send more requests. Default: 5 minutes.', 'iranian-league-table' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Structured data', 'iranian-league-table' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="<?php echo esc_attr( ILT_Api::SETTINGS_OPTION ); ?>[structured_data]" value="1" <?php checked( ILT_Api::structured_data_enabled() ); ?>>
								<?php esc_html_e( 'Add schema.org data (league and teams) to pages that show a table', 'iranian-league-table' ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'Helps search engines understand the table. On by default.', 'iranian-league-table' ); ?></p>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>

			<h2><?php esc_html_e( 'Data status', 'iranian-league-table' ); ?></h2>
			<table class="widefat striped ilt-status">
				<thead>
					<tr>
						<th scope="col"><?php esc_html_e( 'League', 'iranian-league-table' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Last successful update', 'iranian-league-table' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Last error', 'iranian-league-table' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Shown on the site', 'iranian-league-table' ); ?></th>
						<th scope="col"><span class="screen-reader-text"><?php esc_html_e( 'Actions', 'iranian-league-table' ); ?></span></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( ILT_Leagues::all() as $slug => $league ) : ?>
						<?php
						$last  = ILT_Api::last_good( $slug );
						$error = ILT_Api::last_error( $slug );
						?>
						<tr>
							<th scope="row"><?php echo esc_html( $league['label'] ); ?></th>
							<td><?php echo $last ? esc_html( self::ago( $last['time'] ) ) : '—'; ?></td>
							<td><?php echo $error ? esc_html( self::ago( $error['time'] ) . ': ' . $error['message'] ) : '—'; ?></td>
							<td><?php echo in_array( $slug, $used, true ) ? esc_html__( 'Yes', 'iranian-league-table' ) : esc_html__( 'No', 'iranian-league-table' ); ?></td>
							<td>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
									<input type="hidden" name="action" value="ilt_refresh_league">
									<input type="hidden" name="league" value="<?php echo esc_attr( $slug ); ?>">
									<?php wp_nonce_field( 'ilt_refresh_' . $slug ); ?>
									<button type="submit" class="button button-small"><?php esc_html_e( 'Refresh now', 'iranian-league-table' ); ?></button>
								</form>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<h2><?php esc_html_e( 'Diagnostics', 'iranian-league-table' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Cached logos', 'iranian-league-table' ); ?></th>
					<td>
						<?php echo esc_html( number_format_i18n( ILT_Logos::count() ) ); ?>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="ilt-inline-form">
							<input type="hidden" name="action" value="ilt_clear_logos">
							<?php wp_nonce_field( 'ilt_clear_logos' ); ?>
							<button type="submit" class="button button-small"><?php esc_html_e( 'Clear logo cache', 'iranian-league-table' ); ?></button>
						</form>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Logo folder', 'iranian-league-table' ); ?></th>
					<td>
						<code dir="ltr"><?php echo esc_html( ILT_Logos::dir() ); ?></code>
						<?php if ( ILT_Logos::ensure_dir() ) : ?>
							<span class="ilt-ok"><?php esc_html_e( 'Writable', 'iranian-league-table' ); ?></span>
						<?php else : ?>
							<span class="ilt-bad"><?php esc_html_e( 'Not writable: logos are loaded from Varzesh3 instead.', 'iranian-league-table' ); ?></span>
						<?php endif; ?>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Background refresh', 'iranian-league-table' ); ?></th>
					<td>
						<?php
						$next = wp_next_scheduled( ILT_Api::REFRESH_ALL_HOOK );
						if ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) {
							echo '<span class="ilt-bad">' . esc_html__( 'WP-Cron is disabled (DISABLE_WP_CRON). Make sure a real cron job calls wp-cron.php, or the data will only update when you click "Refresh now".', 'iranian-league-table' ) . '</span><br>';
						}
						/* translators: %s: relative time, e.g. "in 3 minutes". */
						echo $next ? esc_html( sprintf( __( 'Next run: %s', 'iranian-league-table' ), self::ago( $next ) ) ) : esc_html__( 'Not scheduled', 'iranian-league-table' );
						?>
					</td>
				</tr>
			</table>
		</div>
		<?php
	}

	/**
	 * Relative time.
	 *
	 * @param int $time Timestamp.
	 * @return string
	 */
	private static function ago( $time ) {
		$diff = human_time_diff( (int) $time, time() );
		/* translators: %s: human time difference. */
		return (int) $time <= time() ? sprintf( __( '%s ago', 'iranian-league-table' ), $diff ) : sprintf( __( 'in %s', 'iranian-league-table' ), $diff );
	}

	/**
	 * Handles admin-post: refresh one league now (and its logos).
	 */
	public static function handle_refresh() {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified with check_admin_referer() below.
		$slug = isset( $_POST['league'] ) ? ILT_Leagues::find( sanitize_text_field( wp_unslash( $_POST['league'] ) ) ) : null;
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to do that.', 'iranian-league-table' ), '', array( 'response' => 403 ) );
		}
		if ( null === $slug ) {
			wp_die( esc_html__( 'Invalid request.', 'iranian-league-table' ), '', array( 'response' => 400 ) );
		}
		check_admin_referer( 'ilt_refresh_' . $slug );

		delete_transient( 'ilt_lock_' . $slug );
		$ok = ILT_Api::refresh( $slug );
		if ( $ok ) {
			ILT_Logos::sync( $slug );
		}
		wp_safe_redirect( ILT_Admin::url( ILT_Admin::SETTINGS_SLUG, array( 'ilt_notice' => $ok ? 'refreshed' : 'refresh_failed' ) ) );
		exit;
	}

	/**
	 * Handles admin-post: delete cached logos and queue a re-download.
	 */
	public static function handle_clear_logos() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to do that.', 'iranian-league-table' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( 'ilt_clear_logos' );
		ILT_Logos::delete_all();
		foreach ( ILT_Api::used_leagues() as $slug ) {
			ILT_Logos::schedule_sync( $slug );
		}
		wp_safe_redirect( ILT_Admin::url( ILT_Admin::SETTINGS_SLUG, array( 'ilt_notice' => 'logos_cleared' ) ) );
		exit;
	}
}
