<?php
/**
 * Color presets for the Shortcode Builder.
 *
 * @package Iranian_League_Table
 */

defined( 'ABSPATH' ) || exit;

/**
 * Named color sets that fill all color options at once.
 */
final class ILT_Presets {

	/**
	 * All presets, filterable through `ilt_presets`.
	 *
	 * @return array<string, array{label:string, values:array<string,string>}>
	 */
	public static function all() {
		$presets = array(
			'classic'     => array(
				'label'  => __( 'Classic (default)', 'iranian-league-table' ),
				'values' => self::colors( '#212121', '#eeeeee', '#212121', '#ffffff', '#eeeeee' ),
			),
			'light'       => array(
				'label'  => __( 'Light', 'iranian-league-table' ),
				'values' => self::colors( '#e5e7eb', '#111827', '#1f2937', '#ffffff', '#f9fafb' ),
			),
			'dark'        => array(
				'label'  => __( 'Dark', 'iranian-league-table' ),
				'values' => self::colors( '#000000', '#f5f5f5', '#e5e5e5', '#1e1e1e', '#2a2a2a' ),
			),
			'persiangulf' => array(
				'label'  => __( 'Persian Gulf blue', 'iranian-league-table' ),
				'values' => self::colors( '#0b4f8a', '#ffffff', '#0f172a', '#ffffff', '#e8f1fa' ),
			),
			'esteghlal'   => array(
				'label'  => __( 'Esteghlal blue', 'iranian-league-table' ),
				'values' => self::colors( '#0033a0', '#ffffff', '#0b1f4d', '#ffffff', '#e6ecf8' ),
			),
			'persepolis'  => array(
				'label'  => __( 'Persepolis red', 'iranian-league-table' ),
				'values' => self::colors( '#c8102e', '#ffffff', '#3b0a12', '#ffffff', '#fbe9ec' ),
			),
			'damash'      => array(
				'label'  => __( 'Damash blue and red', 'iranian-league-table' ),
				'values' => self::colors( '#1d3f91', '#ffffff', '#0d1b3e', '#ffffff', '#fcebec' ),
			),
			'neutral'     => array(
				'label'  => __( 'Neutral gray', 'iranian-league-table' ),
				'values' => self::colors( '#4b5563', '#ffffff', '#374151', '#ffffff', '#f3f4f6' ),
			),
		);

		/**
		 * Filters Builder presets. Each: [ 'label' => string, 'values' => [ attribute => color ] ].
		 *
		 * @param array $presets Presets keyed by id.
		 */
		$presets = apply_filters( 'ilt_presets', $presets );

		$clean = array();
		foreach ( (array) $presets as $id => $preset ) {
			if ( ! isset( $preset['label'], $preset['values'] ) || ! is_array( $preset['values'] ) ) {
				continue;
			}
			$values                       = array_intersect_key( ILT_Attributes::sanitize( $preset['values'] ), $preset['values'] );
			$clean[ sanitize_key( $id ) ] = array(
				'label'  => (string) $preset['label'],
				'values' => $values,
			);
		}
		return $clean;
	}

	/**
	 * Builds a color set.
	 *
	 * @param string $head_bg Header background.
	 * @param string $head_fg Header text.
	 * @param string $text    Row text.
	 * @param string $odd     Odd rows.
	 * @param string $even    Even rows.
	 * @return array<string, string>
	 */
	private static function colors( $head_bg, $head_fg, $text, $odd, $even ) {
		return array(
			'title_backcolor' => $head_bg,
			'title_color'     => $head_fg,
			'text_color'      => $text,
			'odd_color'       => $odd,
			'even_color'      => $even,
		);
	}
}
