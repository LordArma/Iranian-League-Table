<?php
/**
 * Standings → HTML.
 *
 * @package Iranian_League_Table
 */

defined( 'ABSPATH' ) || exit;

/**
 * Renders standings with normalized options. Does no I/O besides loading the template.
 */
final class ILT_Renderer {

	const STYLE_HANDLE = 'ilt-style';

	/**
	 * Renders a league by slug, fetching data through the cache.
	 *
	 * @param array $values Normalized options (see ILT_Attributes).
	 * @return string
	 */
	public static function render_league( $values ) {
		$values = ILT_Attributes::sanitize( $values );
		$data   = ILT_Api::get_standings( $values['league'] );
		if ( null === $data ) {
			self::enqueue_style();
			return self::unavailable();
		}
		$last = ILT_Api::last_good( $values['league'] );
		return self::render( $data, $values, array( 'updated' => $last ? (int) $last['time'] : 0 ) );
	}

	/**
	 * Renders standings.
	 *
	 * @param object $data   Decoded standings with a `teams` array.
	 * @param array  $values Normalized options.
	 * @param array  $meta   Extra info: `updated` (timestamp of the data, 0 if unknown).
	 * @return string
	 */
	public static function render( $data, $values, $meta = array() ) {
		$values = ILT_Attributes::sanitize( $values );

		/**
		 * Filters the standings before rendering.
		 *
		 * @param object $data   Decoded standings.
		 * @param array  $values Normalized display options.
		 */
		$data = apply_filters( 'ilt_table_data', $data, $values );
		if ( ! ILT_Api::is_valid( $data ) ) {
			return self::unavailable();
		}

		self::enqueue_style();

		$ilt = self::view_model( $data, $values, $meta );
		ob_start();
		include self::template_path();
		$html = trim( (string) ob_get_clean() );
		// Collapse whitespace between tags so wpautop or page builders can't inject <br>/<p> into the table.
		$html  = preg_replace( '/>\s+</', '><', $html );
		$html .= self::structured_data( $data );

		/**
		 * Filters the final table HTML.
		 *
		 * @param string $html   Table markup.
		 * @param object $data   Decoded standings.
		 * @param array  $values Normalized display options.
		 */
		return (string) apply_filters( 'ilt_table_html', $html, $data, $values );
	}

	/**
	 * Data passed to the template as `$ilt`.
	 *
	 * @param object $data   Decoded standings.
	 * @param array  $values Normalized options.
	 * @param array  $meta   Extra info (see render()).
	 * @return array
	 */
	public static function view_model( $data, $values, $meta = array() ) {
		$fa        = (bool) $values['farsi_numbers'];
		$teams     = array_values(
			array_filter(
				$data->teams,
				function ( $team ) {
					return is_object( $team );
				}
			)
		);
		$count     = count( $teams );
		$highlight = self::normalize_name( $values['highlight'] );
		$rows      = array();
		foreach ( $teams as $index => $team ) {
			$rank    = $index + 1;
			$name    = isset( $team->name ) && is_scalar( $team->name ) ? (string) $team->name : '';
			$num     = function ( $field ) use ( $team, $fa ) {
				return self::number( isset( $team->$field ) ? (int) $team->$field : 0, $fa );
			};
			$classes = array();
			if ( $rank <= $values['highlight_top'] ) {
				$classes[] = 'ilt-row--top';
			} elseif ( $rank > $count - $values['highlight_bottom'] ) {
				$classes[] = 'ilt-row--bottom';
			}
			if ( '' !== $highlight && self::normalize_name( $name ) === $highlight ) {
				$classes[] = 'ilt-row--highlight';
			}
			$rows[] = array(
				'rank'   => self::number( $rank, $fa ),
				'name'   => $name,
				'link'   => $values['team_links'] ? self::varzesh3_url( isset( $team->link ) ? $team->link : '' ) : '',
				'logo'   => $values['logo'] ? ILT_Logos::url( isset( $team->logo ) ? $team->logo : '' ) : '',
				'class'  => implode( ' ', $classes ),
				'played' => $num( 'played' ),
				'wins'   => $num( 'wins' ),
				'draws'  => $num( 'draws' ),
				'losses' => $num( 'losses' ),
				'goals'  => $num( 'goalFor' ) . '-' . $num( 'goalAgainst' ),
				'diff'   => $num( 'goalDifference' ),
				'points' => $num( 'points' ),
			);
		}
		$rows = self::limit_rows( $rows, $values );

		$vars = array(
			'--ilt-head-bg'   => $values['title_backcolor'],
			'--ilt-head-fg'   => $values['title_color'],
			'--ilt-text'      => $values['text_color'],
			'--ilt-odd'       => $values['odd_color'],
			'--ilt-even'      => $values['even_color'],
			'--ilt-logo-size' => $values['logo_size'] . 'px',
			'--ilt-head-font' => $values['title_font'] . 'px',
			'--ilt-body-font' => $values['text_font'] . 'px',
		);
		// Optional colors are only emitted when used, keeping the default markup unchanged.
		if ( $values['highlight_top'] ) {
			$vars['--ilt-top'] = $values['top_color'];
		}
		if ( $values['highlight_bottom'] ) {
			$vars['--ilt-bottom'] = $values['bottom_color'];
		}
		if ( '' !== $values['highlight'] ) {
			$vars['--ilt-highlight'] = $values['highlight_color'];
		}
		$style = '';
		foreach ( $vars as $name => $value ) {
			$style .= $name . ':' . $value . ';';
		}

		$legend = array();
		if ( $values['highlight_top'] ) {
			$legend[] = array(
				'class' => 'top',
				'label' => '' !== $values['top_label'] ? $values['top_label'] : ILT_Leagues::zone_label( $values['league'], 'top' ),
			);
		}
		if ( $values['highlight_bottom'] ) {
			$legend[] = array(
				'class' => 'bottom',
				'label' => '' !== $values['bottom_label'] ? $values['bottom_label'] : ILT_Leagues::zone_label( $values['league'], 'bottom' ),
			);
		}

		$classes = 'il-table ilt ilt--' . $values['mode'] . ' ilt--' . $values['league'];
		if ( $values['dark_mode'] ) {
			$classes .= ' ilt--auto-dark';
		}

		$updated = ! empty( $meta['updated'] ) ? (int) $meta['updated'] : 0;

		return array(
			'league'        => $values['league'],
			'title'         => isset( $data->title ) && is_scalar( $data->title ) ? (string) $data->title : ILT_Leagues::label( $values['league'] ),
			'advanced'      => 'advanced' === $values['mode'],
			'show_logo'     => (bool) $values['logo'],
			'logo_size'     => (int) $values['logo_size'],
			'wrapper_class' => $classes,
			'style'         => $style,
			'rows'          => $rows,
			'legend'        => $legend,
			'updated'       => $values['show_updated'] && $updated ? ILT_Jalali::format( $updated, $fa ) : '',
			'source'        => $values['show_source'] ? self::varzesh3_url( isset( $data->link ) ? $data->link : '', 'https://www.varzesh3.com/' ) : '',
			'values'        => $values,
		);
	}

	/**
	 * Applies `around` (team ± 2 rows) or `rows` (first N rows).
	 *
	 * @param array $rows   All rows.
	 * @param array $values Normalized options.
	 * @return array
	 */
	public static function limit_rows( $rows, $values ) {
		if ( '' !== $values['around'] ) {
			$target = self::normalize_name( $values['around'] );
			foreach ( $rows as $i => $row ) {
				if ( self::normalize_name( $row['name'] ) === $target ) {
					$size  = min( 5, count( $rows ) );
					$start = max( 0, min( $i - 2, count( $rows ) - $size ) );
					return array_slice( $rows, $start, $size );
				}
			}
		}
		if ( $values['rows'] > 0 ) {
			return array_slice( $rows, 0, $values['rows'] );
		}
		return $rows;
	}

	/**
	 * Normalizes a team name for matching: Arabic ye/kaf to Persian, no ZWNJ, collapsed spaces, lower case.
	 *
	 * @param string $name Team name.
	 * @return string
	 */
	public static function normalize_name( $name ) {
		$name = str_replace( array( 'ي', 'ى', 'ك', "\u{200C}" ), array( 'ی', 'ی', 'ک', ' ' ), (string) $name );
		$name = trim( preg_replace( '/\s+/u', ' ', $name ) );
		return function_exists( 'mb_strtolower' ) ? mb_strtolower( $name ) : strtolower( $name );
	}

	/**
	 * Returns the URL if it points to varzesh3.com over http(s), otherwise `$fallback`.
	 *
	 * @param mixed  $url      URL from the API.
	 * @param string $fallback Fallback URL.
	 * @return string
	 */
	public static function varzesh3_url( $url, $fallback = '' ) {
		if ( ! ILT_Logos::is_http_url( $url ) ) {
			return $fallback;
		}
		$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
		return ( 'varzesh3.com' === $host || '.varzesh3.com' === substr( $host, -13 ) ) ? $url : $fallback;
	}

	/**
	 * schema.org JSON-LD (on by default; Settings → "Structured data").
	 *
	 * @param object $data Decoded standings.
	 * @return string
	 */
	public static function structured_data( $data ) {
		if ( ! ILT_Api::structured_data_enabled() ) {
			return '';
		}
		$members = array();
		foreach ( $data->teams as $team ) {
			if ( is_object( $team ) && isset( $team->name ) && is_scalar( $team->name ) ) {
				$member = array(
					'@type' => 'SportsTeam',
					'name'  => (string) $team->name,
					'sport' => 'Soccer',
				);
				$link   = self::varzesh3_url( isset( $team->link ) ? $team->link : '' );
				if ( '' !== $link ) {
					$member['url'] = $link;
				}
				$members[] = $member;
			}
		}
		$org  = array(
			'@context' => 'https://schema.org',
			'@type'    => 'SportsOrganization',
			'name'     => isset( $data->title ) && is_scalar( $data->title ) ? (string) $data->title : '',
			'sport'    => 'Soccer',
			'member'   => $members,
		);
		$link = self::varzesh3_url( isset( $data->link ) ? $data->link : '' );
		if ( '' !== $link ) {
			$org['url'] = $link;
		}
		// JSON_HEX_TAG turns "<" and ">" into \u003C / \u003E, so remote names cannot close the script tag.
		return '<script type="application/ld+json">' . wp_json_encode( $org, JSON_HEX_TAG ) . '</script>';
	}

	/**
	 * Converts Latin digits to Persian digits when requested.
	 *
	 * @param int|string $value Number.
	 * @param bool       $farsi Whether to use Persian digits.
	 * @return string
	 */
	public static function number( $value, $farsi ) {
		$value = (string) $value;
		return $farsi ? strtr(
			$value,
			array(
				'0' => '۰',
				'1' => '۱',
				'2' => '۲',
				'3' => '۳',
				'4' => '۴',
				'5' => '۵',
				'6' => '۶',
				'7' => '۷',
				'8' => '۸',
				'9' => '۹',
			)
		) : $value;
	}

	/**
	 * Template path: {theme}/iranian-league-table/table.php overrides the plugin template.
	 *
	 * @return string
	 */
	public static function template_path() {
		$theme = locate_template( array( 'iranian-league-table/table.php' ) );
		return $theme ? $theme : ILT_PLUGIN_DIR . 'templates/table.php';
	}

	/**
	 * Message shown when no data is available yet.
	 *
	 * @return string
	 */
	public static function unavailable() {
		return '<div class="il-table ilt ilt--unavailable" dir="rtl"><p class="ilt-unavailable">' . esc_html__( 'League table data is temporarily unavailable. Please try again later.', 'iranian-league-table' ) . '</p></div>';
	}

	/**
	 * Registers the stylesheet (called on init).
	 */
	public static function register_style() {
		wp_register_style( self::STYLE_HANDLE, plugins_url( 'assets/css/style.css', ILT_PLUGIN_FILE ), array(), ILT_VERSION );
	}

	/**
	 * Enqueues the stylesheet; when called after wp_head WordPress prints it in the footer.
	 */
	public static function enqueue_style() {
		if ( ! wp_style_is( self::STYLE_HANDLE, 'registered' ) ) {
			self::register_style();
		}
		wp_enqueue_style( self::STYLE_HANDLE );
	}
}
