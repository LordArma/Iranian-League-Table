<?php
/**
 * Updates from GitHub Releases.
 *
 * @package Iranian_League_Table
 */

defined( 'ABSPATH' ) || exit;

/**
 * Offers new GitHub releases as normal plugin updates (WordPress 5.8+ "Update URI").
 * The plugin header points Update URI at github.com, so WordPress.org is never asked about this plugin.
 * Remove this class and the Update URI header if the plugin is ever published on WordPress.org.
 */
final class ILT_Updater {

	const REPO      = 'LordArma/Iranian-League-Table';
	const SLUG      = 'iranian-league-table';
	const CACHE_KEY = 'ilt_github_release';

	/**
	 * Hooks.
	 */
	public static function init() {
		/**
		 * Filters whether the plugin checks GitHub for updates.
		 *
		 * @param bool $enabled Default true.
		 */
		if ( ! apply_filters( 'ilt_update_checker_enabled', true ) ) {
			return;
		}
		add_filter( 'update_plugins_github.com', array( __CLASS__, 'check' ), 10, 3 );
		add_filter( 'plugins_api', array( __CLASS__, 'info' ), 20, 3 );
		add_action( 'upgrader_process_complete', array( __CLASS__, 'flush' ) );
	}

	/**
	 * Supplies update data for this plugin.
	 *
	 * @param array|false $update      Update data from other filters.
	 * @param array       $plugin_data Plugin headers.
	 * @param string      $plugin_file Plugin basename.
	 * @return array|false
	 */
	public static function check( $update, $plugin_data, $plugin_file ) {
		if ( plugin_basename( ILT_PLUGIN_FILE ) !== $plugin_file ) {
			return $update;
		}
		$release = self::latest_release();
		if ( null === $release ) {
			return $update;
		}
		return array(
			'id'           => 'github.com/' . self::REPO,
			'slug'         => self::SLUG,
			'plugin'       => $plugin_file,
			'version'      => $release['version'],
			'url'          => $release['url'],
			// Only offer a package when the release is newer; otherwise WordPress reports "up to date".
			'package'      => version_compare( $release['version'], ILT_VERSION, '>' ) ? $release['package'] : '',
			'requires_php' => '7.4',
			'requires'     => '6.0',
		);
	}

	/**
	 * "View details" popup.
	 *
	 * @param false|object|array $result Result.
	 * @param string             $action API action.
	 * @param object             $args   Arguments.
	 * @return false|object|array
	 */
	public static function info( $result, $action, $args ) {
		if ( 'plugin_information' !== $action || ! isset( $args->slug ) || self::SLUG !== $args->slug ) {
			return $result;
		}
		$release = self::latest_release();
		if ( null === $release ) {
			return $result;
		}
		return (object) array(
			'name'          => 'Iranian League Table',
			'slug'          => self::SLUG,
			'version'       => $release['version'],
			'author'        => '<a href="https://LordArma.com">Arma</a>',
			'homepage'      => 'https://github.com/' . self::REPO,
			'requires'      => '6.0',
			'requires_php'  => '7.4',
			'download_link' => $release['package'],
			'last_updated'  => $release['published'],
			'sections'      => array(
				'changelog' => '<pre>' . esc_html( $release['notes'] ) . '</pre>',
			),
		);
	}

	/**
	 * Latest release with a plugin zip, cached for 12 hours (1 hour after a failed request).
	 *
	 * @return array{version:string, package:string, url:string, notes:string, published:string}|null
	 */
	public static function latest_release() {
		$cached = get_site_transient( self::CACHE_KEY );
		if ( is_array( $cached ) ) {
			return empty( $cached['version'] ) ? null : $cached;
		}

		$release  = null;
		$response = wp_remote_get(
			'https://api.github.com/repos/' . self::REPO . '/releases/latest',
			array(
				'timeout' => 8,
				'headers' => array( 'Accept' => 'application/vnd.github+json' ),
			)
		);
		if ( ! is_wp_error( $response ) && 200 === (int) wp_remote_retrieve_response_code( $response ) ) {
			$release = self::parse_release( json_decode( wp_remote_retrieve_body( $response ), true ) );
		}
		set_site_transient( self::CACHE_KEY, $release ? $release : array(), $release ? 12 * HOUR_IN_SECONDS : HOUR_IN_SECONDS );
		return $release;
	}

	/**
	 * Extracts what we need from a GitHub release.
	 *
	 * @param mixed $json Decoded release.
	 * @return array|null
	 */
	public static function parse_release( $json ) {
		if ( ! is_array( $json ) || empty( $json['tag_name'] ) || ! empty( $json['draft'] ) || ! empty( $json['prerelease'] ) ) {
			return null;
		}
		$version = ltrim( (string) $json['tag_name'], 'vV' );
		if ( ! preg_match( '/^\d+\.\d+(\.\d+)?$/', $version ) ) {
			return null;
		}
		foreach ( isset( $json['assets'] ) ? (array) $json['assets'] : array() as $asset ) {
			$name = isset( $asset['name'] ) ? (string) $asset['name'] : '';
			$url  = isset( $asset['browser_download_url'] ) ? (string) $asset['browser_download_url'] : '';
			if ( preg_match( '/^iranian-league-table(-[\d.]+)?\.zip$/', $name ) && 0 === strpos( $url, 'https://github.com/' . self::REPO . '/' ) ) {
				return array(
					'version'   => $version,
					'package'   => $url,
					'url'       => isset( $json['html_url'] ) ? (string) $json['html_url'] : 'https://github.com/' . self::REPO . '/releases',
					'notes'     => isset( $json['body'] ) ? (string) $json['body'] : '',
					'published' => isset( $json['published_at'] ) ? (string) $json['published_at'] : '',
				);
			}
		}
		return null;
	}

	/**
	 * Clears the cached release after updates.
	 */
	public static function flush() {
		delete_site_transient( self::CACHE_KEY );
	}
}
