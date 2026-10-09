<?php
/**
 * Team logo cache.
 *
 * @package Iranian_League_Table
 */

defined( 'ABSPATH' ) || exit;

/**
 * Keeps local copies of team logos in wp-content/uploads/iranian-league-table/.
 * Downloads only happen in WP-Cron, never while a page renders; until a logo is cached
 * the remote URL is used.
 */
final class ILT_Logos {

	const SYNC_HOOK = 'ilt_sync_logos';
	const MAX_AGE   = DAY_IN_SECONDS;
	const MAX_BYTES = 1048576;

	/**
	 * Local directory for cached logos (with trailing slash).
	 *
	 * @return string
	 */
	public static function dir() {
		$upload = wp_upload_dir( null, false );
		return trailingslashit( $upload['basedir'] ) . 'iranian-league-table/';
	}

	/**
	 * Public URL of the logo directory (with trailing slash).
	 *
	 * @return string
	 */
	public static function base_url() {
		$upload = wp_upload_dir( null, false );
		return trailingslashit( set_url_scheme( $upload['baseurl'] ) ) . 'iranian-league-table/';
	}

	/**
	 * Cache file name for a remote logo URL: never derived from team names.
	 *
	 * @param string $remote Remote URL.
	 * @return string
	 */
	public static function filename( $remote ) {
		$ext = strtolower( pathinfo( (string) wp_parse_url( $remote, PHP_URL_PATH ), PATHINFO_EXTENSION ) );
		if ( ! in_array( $ext, array( 'png', 'jpg', 'jpeg', 'gif', 'webp' ), true ) ) {
			$ext = 'png';
		}
		return 'logo-' . md5( $remote ) . '.' . $ext;
	}

	/**
	 * Whether a URL is an http(s) URL.
	 *
	 * @param mixed $url URL.
	 * @return bool
	 */
	public static function is_http_url( $url ) {
		return is_string( $url ) && (bool) preg_match( '#^https?://[^\s"\'<>]+$#i', $url );
	}

	/**
	 * URL to display for a team logo: the cached copy if present, else the remote URL, else ''.
	 *
	 * @param mixed $remote Remote URL from the API.
	 * @return string
	 */
	public static function url( $remote ) {
		if ( ! self::is_http_url( $remote ) ) {
			return '';
		}
		$name = self::filename( $remote );
		return file_exists( self::dir() . $name ) ? self::base_url() . $name : $remote;
	}

	/**
	 * Queues a logo sync for a league (once).
	 *
	 * @param string $slug Canonical league slug.
	 */
	public static function schedule_sync( $slug ) {
		if ( ! wp_next_scheduled( self::SYNC_HOOK, array( $slug ) ) ) {
			wp_schedule_single_event( time() + 5, self::SYNC_HOOK, array( $slug ) );
		}
	}

	/**
	 * Cron callback: downloads missing or old logos for a league.
	 *
	 * @param string $slug Canonical league slug.
	 * @return int Number of logos downloaded.
	 */
	public static function sync( $slug ) {
		$last = ILT_Api::last_good( $slug );
		if ( null === $last || ! self::ensure_dir() ) {
			return 0;
		}
		$done  = array();
		$count = 0;
		foreach ( $last['data']->teams as $team ) {
			$remote = is_object( $team ) && isset( $team->logo ) ? $team->logo : '';
			if ( ! self::is_http_url( $remote ) || isset( $done[ $remote ] ) ) {
				continue;
			}
			$done[ $remote ] = true;
			$file            = self::dir() . self::filename( $remote );
			if ( file_exists( $file ) && filemtime( $file ) > time() - self::MAX_AGE ) {
				continue;
			}
			if ( self::download( $remote, $file ) ) {
				++$count;
			}
		}
		return $count;
	}

	/**
	 * Downloads one image and writes it atomically. Broken or non-image responses are discarded.
	 *
	 * @param string $url  Remote URL.
	 * @param string $file Destination path.
	 * @return bool
	 */
	public static function download( $url, $file ) {
		$response = wp_safe_remote_get(
			$url,
			array(
				'timeout'             => 5,
				'limit_response_size' => self::MAX_BYTES,
			)
		);
		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			return false;
		}
		$body = wp_remote_retrieve_body( $response );
		$type = (string) wp_remote_retrieve_header( $response, 'content-type' );
		if ( '' === $body || strlen( $body ) >= self::MAX_BYTES || ( '' !== $type && 0 !== stripos( $type, 'image/' ) ) ) {
			return false;
		}
		$tmp = $file . '.' . wp_generate_password( 8, false ) . '.tmp';
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Direct write into our own uploads subfolder.
		if ( false === file_put_contents( $tmp, $body ) ) {
			return false;
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions.rename_rename -- Atomic replace.
		if ( ! rename( $tmp, $file ) ) {
			wp_delete_file( $tmp );
			return false;
		}
		return true;
	}

	/**
	 * Creates the logo directory (with an index.php) if needed.
	 *
	 * @return bool Whether the directory is writable.
	 */
	public static function ensure_dir() {
		$dir = self::dir();
		if ( ! wp_mkdir_p( $dir ) ) {
			return false;
		}
		if ( ! file_exists( $dir . 'index.php' ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			file_put_contents( $dir . 'index.php', "<?php\n// Silence is golden.\n" );
		}
		return wp_is_writable( $dir );
	}

	/**
	 * Number of cached logos.
	 *
	 * @return int
	 */
	public static function count() {
		$files = glob( self::dir() . 'logo-*' );
		return is_array( $files ) ? count( $files ) : 0;
	}

	/**
	 * Deletes the logo directory and its contents.
	 */
	public static function delete_all() {
		$dir   = self::dir();
		$files = glob( $dir . '*' );
		foreach ( is_array( $files ) ? $files : array() as $file ) {
			if ( is_file( $file ) ) {
				wp_delete_file( $file );
			}
		}
		if ( is_dir( $dir ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir
			rmdir( $dir );
		}
	}
}
