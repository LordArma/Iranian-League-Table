<?php
/**
 * League registry.
 *
 * @package Iranian_League_Table
 */

defined( 'ABSPATH' ) || exit;

/**
 * Known leagues: canonical slug => Varzesh3 league id, label and accepted shortcode aliases.
 */
final class ILT_Leagues {

	const DEFAULT_SLUG = 'persiangulf';

	const API_URL = 'https://web-api.varzesh3.com/v1.0/developer-tools/football/leagues/%d/standing';

	/**
	 * All leagues, filterable through `ilt_leagues`.
	 *
	 * Each entry: `id` (Varzesh3 league id), `label`, `aliases` (strings accepted by `league="..."`), and optional
	 * `top_label` / `bottom_label`: default legend texts for highlighted top and bottom rows (shown in Persian on the site).
	 *
	 * @return array<string, array{id:int, label:string, aliases:string[], top_label?:string, bottom_label?:string}>
	 */
	public static function all() {
		$leagues = array(
			'persiangulf' => array(
				'id'           => 6,
				'label'        => __( 'Persian Gulf League', 'iranian-league-table' ),
				'aliases'      => array( 'persiangulf', 'bartar' ),
				'top_label'    => 'سهمیه آسیایی',
				'bottom_label' => 'سقوط به لیگ یک',
			),
			'azadegan'    => array(
				'id'           => 24,
				'label'        => __( 'League One', 'iranian-league-table' ),
				'aliases'      => array( 'azadegan', 'one', '1', 'yek', 'lige1', 'leagueone', 'ligeyek', 'لیگ۱', 'آزادگان' ),
				'top_label'    => 'صعود به لیگ برتر',
				'bottom_label' => 'سقوط به لیگ دو',
			),
			'kowsar'      => array(
				'id'           => 54,
				'label'        => __( 'League Kowsar', 'iranian-league-table' ),
				'aliases'      => array( 'kowsar', 'kosar', 'women', 'woman', 'zanan', 'banovan', 'leaguekosar', 'زنان', 'بانو', 'بانوان' ),
				'top_label'    => 'قهرمانی',
				'bottom_label' => 'سقوط',
			),
		);

		/**
		 * Filters the available leagues.
		 *
		 * @param array $leagues Slug => [ id, label, aliases ].
		 */
		$leagues = apply_filters( 'ilt_leagues', $leagues );

		return is_array( $leagues ) && $leagues ? $leagues : array();
	}

	/**
	 * Maps a slug or alias to a canonical slug, or null when it isn't recognized.
	 *
	 * @param mixed $value Attribute value.
	 * @return string|null
	 */
	public static function find( $value ) {
		$value = trim( (string) $value );
		foreach ( self::all() as $slug => $league ) {
			if ( $value === (string) $slug || in_array( $value, (array) $league['aliases'], true ) ) {
				return (string) $slug;
			}
		}
		return null;
	}

	/**
	 * Maps any value to a canonical slug; unknown values fall back to the Persian Gulf league,
	 * as they always have.
	 *
	 * @param mixed $value Attribute value.
	 * @return string
	 */
	public static function resolve( $value ) {
		$slug = self::find( $value );
		if ( null !== $slug ) {
			return $slug;
		}
		$all = self::all();
		return isset( $all[ self::DEFAULT_SLUG ] ) ? self::DEFAULT_SLUG : (string) key( $all );
	}

	/**
	 * Standings endpoint for a slug.
	 *
	 * @param string $slug Canonical slug.
	 * @return string
	 */
	public static function api_url( $slug ) {
		$all = self::all();
		$id  = isset( $all[ $slug ] ) ? (int) $all[ $slug ]['id'] : 0;
		return sprintf( self::API_URL, $id );
	}

	/**
	 * Default legend text for highlighted top/bottom rows.
	 *
	 * @param string $slug  Canonical slug.
	 * @param string $which top|bottom.
	 * @return string
	 */
	public static function zone_label( $slug, $which ) {
		$all = self::all();
		$key = $which . '_label';
		if ( isset( $all[ $slug ][ $key ] ) && '' !== $all[ $slug ][ $key ] ) {
			return (string) $all[ $slug ][ $key ];
		}
		return 'top' === $which ? 'بالای جدول' : 'سقوط';
	}

	/**
	 * Human-readable league name.
	 *
	 * @param string $slug Canonical slug.
	 * @return string
	 */
	public static function label( $slug ) {
		$all = self::all();
		return isset( $all[ $slug ] ) ? (string) $all[ $slug ]['label'] : (string) $slug;
	}
}
