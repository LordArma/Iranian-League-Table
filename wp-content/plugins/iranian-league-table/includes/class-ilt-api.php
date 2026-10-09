<?php
/**
 * Standings fetching and caching.
 *
 * @package Iranian_League_Table
 */

defined( 'ABSPATH' ) || exit;

/**
 * Stale-while-revalidate cache for league standings.
 *
 * - `ilt_standings_{slug}` transient: fresh copy, lives for the cache TTL.
 * - `ilt_last_good_{slug}` option (not autoloaded): last valid response, kept indefinitely.
 * - When the transient has expired, page views serve the last good copy and a WP-Cron event
 *   refreshes it in the background, so a page view only waits on the API when no copy exists at all.
 */
final class ILT_Api {

	const REFRESH_HOOK     = 'ilt_refresh_league';
	const REFRESH_ALL_HOOK = 'ilt_refresh_all';
	const USED_OPTION      = 'ilt_used_leagues';
	const SETTINGS_OPTION  = 'ilt_settings';
	const DEFAULT_TTL      = 300;
	const TIMEOUT          = 8;

	/**
	 * Returns standings for a league, or null when no data has ever been fetched successfully.
	 *
	 * @param string $slug Canonical league slug.
	 * @return object|null
	 */
	public static function get_standings( $slug ) {
		self::track_usage( $slug );

		$fresh = get_transient( self::transient_key( $slug ) );
		if ( self::is_valid( $fresh ) ) {
			return $fresh;
		}

		$last = self::last_good( $slug );
		if ( null !== $last ) {
			self::schedule_refresh( $slug );
			return $last['data'];
		}

		// Cold cache: nothing to show yet, so fetch now.
		if ( self::refresh( $slug ) ) {
			$last = self::last_good( $slug );
			return $last ? $last['data'] : null;
		}
		return null;
	}

	/**
	 * Fetches a league and updates the cache. Only one request at a time per league does the fetch.
	 *
	 * @param string $slug Canonical league slug.
	 * @return bool True when fresh data was stored.
	 */
	public static function refresh( $slug ) {
		$lock = 'ilt_lock_' . $slug;
		if ( get_transient( $lock ) ) {
			return false;
		}
		set_transient( $lock, 1, 30 );

		$result = self::fetch( ILT_Leagues::api_url( $slug ) );

		if ( is_wp_error( $result ) ) {
			update_option(
				'ilt_last_error_' . $slug,
				array(
					'time'    => time(),
					'message' => $result->get_error_message(),
				),
				false
			);
			// Back off: keep serving the last good copy as "fresh" for a short while instead of retrying on every view.
			$last = self::last_good( $slug );
			if ( null !== $last ) {
				set_transient( self::transient_key( $slug ), $last['data'], min( self::ttl(), 120 ) );
			}
			delete_transient( $lock );
			return false;
		}

		update_option(
			'ilt_last_good_' . $slug,
			array(
				'time' => time(),
				'data' => $result,
			),
			false
		);
		delete_option( 'ilt_last_error_' . $slug );
		set_transient( self::transient_key( $slug ), $result, self::ttl() );
		delete_transient( $lock );

		ILT_Logos::schedule_sync( $slug );
		return true;
	}

	/**
	 * Requests and validates one standings payload.
	 *
	 * @param string $url Endpoint.
	 * @return object|WP_Error
	 */
	public static function fetch( $url ) {
		$response = wp_remote_get(
			$url,
			array(
				'timeout' => self::TIMEOUT,
				'headers' => array( 'Accept' => 'application/json' ),
			)
		);
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		$code = (int) wp_remote_retrieve_response_code( $response );
		if ( 200 !== $code ) {
			/* translators: %d: HTTP status code. */
			return new WP_Error( 'ilt_http', sprintf( __( 'Unexpected HTTP status %d', 'iranian-league-table' ), $code ) );
		}
		$data = json_decode( wp_remote_retrieve_body( $response ), false );
		if ( ! self::is_valid( $data ) ) {
			return new WP_Error( 'ilt_invalid', __( 'The response is not valid standings data', 'iranian-league-table' ) );
		}

		/**
		 * Filters decoded standings right after a successful fetch.
		 *
		 * @param object $data Decoded payload with a `teams` array.
		 * @param string $url  Endpoint.
		 */
		return apply_filters( 'ilt_fetched_data', $data, $url );
	}

	/**
	 * Whether a decoded payload looks like standings.
	 *
	 * @param mixed $data Decoded JSON.
	 * @return bool
	 */
	public static function is_valid( $data ) {
		return is_object( $data ) && isset( $data->teams ) && is_array( $data->teams );
	}

	/**
	 * Last successful response for a league.
	 *
	 * @param string $slug Canonical league slug.
	 * @return array{time:int, data:object}|null
	 */
	public static function last_good( $slug ) {
		$last = get_option( 'ilt_last_good_' . $slug );
		if ( is_array( $last ) && isset( $last['data'] ) && self::is_valid( $last['data'] ) ) {
			return $last;
		}
		return null;
	}

	/**
	 * Last refresh error for a league.
	 *
	 * @param string $slug Canonical league slug.
	 * @return array{time:int, message:string}|null
	 */
	public static function last_error( $slug ) {
		$error = get_option( 'ilt_last_error_' . $slug );
		return is_array( $error ) ? $error : null;
	}

	/**
	 * Queues a background refresh (once).
	 *
	 * @param string $slug Canonical league slug.
	 */
	public static function schedule_refresh( $slug ) {
		if ( ! wp_next_scheduled( self::REFRESH_HOOK, array( $slug ) ) ) {
			wp_schedule_single_event( time(), self::REFRESH_HOOK, array( $slug ) );
		}
	}

	/**
	 * Cron callback: refreshes every league rendered recently.
	 */
	public static function refresh_all() {
		foreach ( self::used_leagues() as $slug ) {
			self::refresh( $slug );
		}
	}

	/**
	 * Remembers which leagues are displayed, so cron only refreshes those.
	 *
	 * @param string $slug Canonical league slug.
	 */
	public static function track_usage( $slug ) {
		$used = get_option( self::USED_OPTION );
		$used = is_array( $used ) ? $used : array();
		// Write at most once an hour per league.
		if ( ! isset( $used[ $slug ] ) || $used[ $slug ] < time() - HOUR_IN_SECONDS ) {
			$used[ $slug ] = time();
			update_option( self::USED_OPTION, $used, false );
		}
	}

	/**
	 * Leagues rendered in the last 7 days.
	 *
	 * @return string[]
	 */
	public static function used_leagues() {
		$used  = get_option( self::USED_OPTION );
		$used  = is_array( $used ) ? $used : array();
		$known = ILT_Leagues::all();
		$slugs = array();
		foreach ( $used as $slug => $time ) {
			if ( isset( $known[ $slug ] ) && $time >= time() - WEEK_IN_SECONDS ) {
				$slugs[] = (string) $slug;
			}
		}
		return $slugs;
	}

	/**
	 * Whether to print schema.org structured data. On unless turned off in Settings.
	 *
	 * @return bool
	 */
	public static function structured_data_enabled() {
		$settings = get_option( self::SETTINGS_OPTION );
		return ! isset( $settings['structured_data'] ) || (bool) $settings['structured_data'];
	}

	/**
	 * Cache lifetime in seconds (60–86400, default 300). Filter: `ilt_cache_ttl`.
	 *
	 * @return int
	 */
	public static function ttl() {
		$settings = get_option( self::SETTINGS_OPTION );
		$ttl      = isset( $settings['cache_ttl'] ) ? (int) $settings['cache_ttl'] : self::DEFAULT_TTL;

		/**
		 * Filters the standings cache lifetime.
		 *
		 * @param int $ttl Seconds.
		 */
		$ttl = (int) apply_filters( 'ilt_cache_ttl', $ttl );
		return max( MINUTE_IN_SECONDS, min( DAY_IN_SECONDS, $ttl ) );
	}

	/**
	 * Drops the fresh copy so the next view (or cron) refetches; the last good copy is kept.
	 *
	 * @param string $slug Canonical league slug.
	 */
	public static function expire( $slug ) {
		delete_transient( self::transient_key( $slug ) );
	}

	/**
	 * Transient name for a league.
	 *
	 * @param string $slug Canonical league slug.
	 * @return string
	 */
	public static function transient_key( $slug ) {
		return 'ilt_standings_' . $slug;
	}
}
