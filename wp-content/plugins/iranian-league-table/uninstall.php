<?php
/**
 * Removes everything the plugin stored.
 *
 * @package Iranian_League_Table
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

/**
 * Deletes this site's plugin data.
 */
function ilt_uninstall_site() {
	global $wpdb;

	foreach ( array( 'ilt_version', 'ilt_settings', 'ilt_used_leagues', 'ilt_last_update', 'ilt_last_logo' ) as $option ) {
		delete_option( $option );
	}

	// Per-league options and transients: ilt_last_good_*, ilt_last_error_*, ilt_standings_*, ilt_lock_*, ilt_table_usage.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- One-off cleanup.
	$names = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s OR option_name LIKE %s OR option_name LIKE %s",
			$wpdb->esc_like( 'ilt_last_good_' ) . '%',
			$wpdb->esc_like( 'ilt_last_error_' ) . '%',
			$wpdb->esc_like( '_transient_ilt_' ) . '%',
			$wpdb->esc_like( '_transient_timeout_ilt_' ) . '%'
		)
	);
	foreach ( $names as $name ) {
		delete_option( $name );
	}

	// Saved tables.
	$tables = get_posts(
		array(
			'post_type'      => 'ilt_table',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		)
	);
	foreach ( $tables as $id ) {
		wp_delete_post( $id, true );
	}

	foreach ( array( 'ilt_refresh_all', 'ilt_refresh_league', 'ilt_sync_logos' ) as $hook ) {
		wp_unschedule_hook( $hook );
	}

	// Cached logos.
	$upload = wp_upload_dir( null, false );
	$dir    = trailingslashit( $upload['basedir'] ) . 'iranian-league-table/';
	$files  = glob( $dir . '*' );
	foreach ( is_array( $files ) ? $files : array() as $file ) {
		if ( is_file( $file ) ) {
			wp_delete_file( $file );
		}
	}
	if ( is_dir( $dir ) ) {
		rmdir( $dir ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir
	}
}

delete_site_transient( 'ilt_github_release' );

if ( is_multisite() ) {
	foreach ( get_sites(
		array(
			'fields' => 'ids',
			'number' => 0,
		)
	) as $ilt_site_id ) {
		switch_to_blog( $ilt_site_id );
		ilt_uninstall_site();
		restore_current_blog();
	}
} else {
	ilt_uninstall_site();
}
