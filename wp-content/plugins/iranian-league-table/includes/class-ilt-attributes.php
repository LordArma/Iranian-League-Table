<?php
/**
 * Display option schema: the single source of truth for the shortcode, the widget,
 * the Shortcode Builder and saved tables.
 *
 * @package Iranian_League_Table
 */

defined( 'ABSPATH' ) || exit;

/**
 * Describes, sanitizes and serializes display options.
 */
final class ILT_Attributes {

	const SHORTCODE = 'iran_league';

	/**
	 * Option schema, keyed by shortcode attribute name.
	 *
	 * Keys per option:
	 * - type:       color | int | bool | enum | league | text
	 * - default:    normalized default value
	 * - invalid:    value used for unrecognized input (defaults to `default`)
	 * - min / max:  bounds for int
	 * - choices:    value => label for enum/league
	 * - section:    content | zones | colors | sizes (Builder grouping)
	 * - label/help: UI text
	 * - widget_key: key used by the legacy widget instance
	 *
	 * @return array<string, array>
	 */
	public static function schema() {
		$leagues = array();
		foreach ( ILT_Leagues::all() as $slug => $league ) {
			$leagues[ $slug ] = $league['label'];
		}

		$schema = array(
			'league'           => array(
				'type'       => 'league',
				'default'    => ILT_Leagues::DEFAULT_SLUG,
				'choices'    => $leagues,
				'section'    => 'content',
				'label'      => __( 'League', 'iranian-league-table' ),
				'help'       => '',
				'widget_key' => 'league',
			),
			'mode'             => array(
				'type'       => 'enum',
				'default'    => 'basic',
				// Historically anything other than "basic" meant advanced.
				'invalid'    => 'advanced',
				'choices'    => array(
					'basic'    => __( 'Basic', 'iranian-league-table' ),
					'advanced' => __( 'Advanced', 'iranian-league-table' ),
				),
				'section'    => 'content',
				'label'      => __( 'Display mode', 'iranian-league-table' ),
				'help'       => __( 'Basic: rank, team, played and points. Advanced: also wins, draws, losses, goals and goal difference.', 'iranian-league-table' ),
				'widget_key' => 'table_type',
			),
			'logo'             => array(
				'type'       => 'bool',
				'default'    => true,
				// Historically only "false" hid the logos.
				'invalid'    => true,
				'section'    => 'content',
				'label'      => __( 'Show team logos', 'iranian-league-table' ),
				'help'       => '',
				'widget_key' => 'logo',
			),
			'farsi_numbers'    => array(
				'type'       => 'bool',
				'default'    => true,
				// Historically only "true" enabled Persian digits.
				'invalid'    => false,
				'section'    => 'content',
				'label'      => __( 'Persian digits', 'iranian-league-table' ),
				'help'       => '',
				'widget_key' => 'farsi_numbers',
			),
			'title_backcolor'  => array(
				'type'       => 'color',
				'default'    => '#212121',
				'section'    => 'colors',
				'label'      => __( 'Header background', 'iranian-league-table' ),
				'help'       => '',
				'widget_key' => 'title_color',
			),
			'title_color'      => array(
				'type'       => 'color',
				'default'    => '#eeeeee',
				'section'    => 'colors',
				'label'      => __( 'Header text', 'iranian-league-table' ),
				'help'       => '',
				'widget_key' => 'title_text_color',
			),
			'text_color'       => array(
				'type'       => 'color',
				'default'    => '#212121',
				'section'    => 'colors',
				'label'      => __( 'Row text', 'iranian-league-table' ),
				'help'       => '',
				'widget_key' => 'text_color',
			),
			'odd_color'        => array(
				'type'       => 'color',
				'default'    => '#ffffff',
				'section'    => 'colors',
				'label'      => __( 'Odd rows background', 'iranian-league-table' ),
				'help'       => '',
				'widget_key' => 'odd_color',
			),
			'even_color'       => array(
				'type'       => 'color',
				'default'    => '#eeeeee',
				'section'    => 'colors',
				'label'      => __( 'Even rows background', 'iranian-league-table' ),
				'help'       => '',
				'widget_key' => 'even_color',
			),
			'logo_size'        => array(
				'type'       => 'int',
				'default'    => 15,
				'min'        => 8,
				'max'        => 64,
				'section'    => 'sizes',
				'label'      => __( 'Logo size (px)', 'iranian-league-table' ),
				'help'       => '',
				'widget_key' => 'logo_size',
			),
			'title_font'       => array(
				'type'       => 'int',
				'default'    => 13,
				'min'        => 8,
				'max'        => 64,
				'section'    => 'sizes',
				'label'      => __( 'Header font size (px)', 'iranian-league-table' ),
				'help'       => '',
				'widget_key' => 'font_h_size',
			),
			'text_font'        => array(
				'type'       => 'int',
				'default'    => 14,
				'min'        => 8,
				'max'        => 64,
				'section'    => 'sizes',
				'label'      => __( 'Body font size (px)', 'iranian-league-table' ),
				'help'       => '',
				'widget_key' => 'font_d_size',
			),
			'rows'             => array(
				'type'       => 'int',
				'default'    => 0,
				'min'        => 0,
				'max'        => 30,
				'section'    => 'content',
				'label'      => __( 'Number of rows', 'iranian-league-table' ),
				'help'       => __( '0 shows the whole table. Handy for sidebars: 5 shows the top five.', 'iranian-league-table' ),
				'widget_key' => 'rows',
			),
			'around'           => array(
				'type'       => 'text',
				'default'    => '',
				'section'    => 'content',
				'label'      => __( 'Show only the rows around a team', 'iranian-league-table' ),
				'help'       => __( 'Team name as written on Varzesh3, e.g. استقلال. Shows that team with two rows above and below it.', 'iranian-league-table' ),
				'widget_key' => 'around',
			),
			'highlight'        => array(
				'type'       => 'text',
				'default'    => '',
				'section'    => 'content',
				'label'      => __( 'Highlight a team', 'iranian-league-table' ),
				'help'       => __( 'Team name as written on Varzesh3, e.g. پرسپولیس. Its row is shown in bold with the highlight color.', 'iranian-league-table' ),
				'widget_key' => 'highlight',
			),
			'team_links'       => array(
				'type'       => 'bool',
				'default'    => false,
				'section'    => 'content',
				'label'      => __( 'Link team names to Varzesh3', 'iranian-league-table' ),
				'help'       => '',
				'widget_key' => 'team_links',
			),
			'show_updated'     => array(
				'type'       => 'bool',
				'default'    => false,
				'section'    => 'content',
				'label'      => __( 'Show when the table was last updated', 'iranian-league-table' ),
				'help'       => __( 'Adds a line under the table with the date and time in the Persian calendar.', 'iranian-league-table' ),
				'widget_key' => 'show_updated',
			),
			'show_source'      => array(
				'type'       => 'bool',
				'default'    => false,
				'section'    => 'content',
				'label'      => __( 'Show "Source: Varzesh3" link', 'iranian-league-table' ),
				'help'       => '',
				'widget_key' => 'show_source',
			),
			'highlight_top'    => array(
				'type'       => 'int',
				'default'    => 0,
				'min'        => 0,
				'max'        => 10,
				'section'    => 'zones',
				'label'      => __( 'Highlight top rows', 'iranian-league-table' ),
				'help'       => __( 'For example 2 for the Asian Champions League places. 0 turns it off.', 'iranian-league-table' ),
				'widget_key' => 'highlight_top',
			),
			'top_label'        => array(
				'type'       => 'text',
				'default'    => '',
				'section'    => 'zones',
				'label'      => __( 'Legend text for top rows', 'iranian-league-table' ),
				'help'       => __( 'Leave empty for the default, e.g. «سهمیه آسیایی».', 'iranian-league-table' ),
				'widget_key' => 'top_label',
			),
			'highlight_bottom' => array(
				'type'       => 'int',
				'default'    => 0,
				'min'        => 0,
				'max'        => 10,
				'section'    => 'zones',
				'label'      => __( 'Highlight bottom rows', 'iranian-league-table' ),
				'help'       => __( 'For example 2 for the relegation places. 0 turns it off.', 'iranian-league-table' ),
				'widget_key' => 'highlight_bottom',
			),
			'bottom_label'     => array(
				'type'       => 'text',
				'default'    => '',
				'section'    => 'zones',
				'label'      => __( 'Legend text for bottom rows', 'iranian-league-table' ),
				'help'       => __( 'Leave empty for the default, e.g. «سقوط».', 'iranian-league-table' ),
				'widget_key' => 'bottom_label',
			),
			'top_color'        => array(
				'type'       => 'color',
				'default'    => '#dff3e4',
				'section'    => 'colors',
				'label'      => __( 'Top rows background', 'iranian-league-table' ),
				'help'       => '',
				'widget_key' => 'top_color',
			),
			'bottom_color'     => array(
				'type'       => 'color',
				'default'    => '#fbe3e3',
				'section'    => 'colors',
				'label'      => __( 'Bottom rows background', 'iranian-league-table' ),
				'help'       => '',
				'widget_key' => 'bottom_color',
			),
			'highlight_color'  => array(
				'type'       => 'color',
				'default'    => '#fff1b8',
				'section'    => 'colors',
				'label'      => __( 'Highlighted team background', 'iranian-league-table' ),
				'help'       => '',
				'widget_key' => 'highlight_color',
			),
			'dark_mode'        => array(
				'type'       => 'bool',
				'default'    => false,
				'section'    => 'colors',
				'label'      => __( 'Use dark colors when the visitor\'s device is in dark mode', 'iranian-league-table' ),
				'help'       => '',
				'widget_key' => 'dark_mode',
			),
		);

		foreach ( $schema as $key => $field ) {
			if ( ! array_key_exists( 'invalid', $field ) ) {
				$schema[ $key ]['invalid'] = $field['default'];
			}
		}
		return $schema;
	}

	/**
	 * Normalized default values.
	 *
	 * @return array<string, mixed>
	 */
	public static function defaults() {
		return wp_list_pluck( self::schema(), 'default' );
	}

	/**
	 * Sanitizes raw values (strings from a shortcode, form or JSON) into a complete, normalized option set.
	 * Unknown keys are dropped; missing keys come from `$base` and then from the defaults.
	 *
	 * @param array      $raw  Raw values keyed by attribute name.
	 * @param array|null $base Already-normalized values to start from.
	 * @return array<string, mixed>
	 */
	public static function sanitize( $raw, $base = null ) {
		$schema = self::schema();
		$raw    = array_change_key_case( (array) $raw, CASE_LOWER );
		$out    = is_array( $base ) ? array_merge( self::defaults(), array_intersect_key( $base, $schema ) ) : self::defaults();
		foreach ( $schema as $key => $field ) {
			if ( array_key_exists( $key, $raw ) ) {
				$out[ $key ] = self::sanitize_value( $field, $raw[ $key ] );
			}
		}
		return $out;
	}

	/**
	 * Sanitizes one value against its schema entry.
	 *
	 * @param array $field Schema entry.
	 * @param mixed $value Raw value.
	 * @return mixed
	 */
	public static function sanitize_value( $field, $value ) {
		if ( is_array( $value ) || is_object( $value ) ) {
			return $field['invalid'];
		}
		switch ( $field['type'] ) {
			case 'league':
				return ILT_Leagues::resolve( $value );

			case 'enum':
				$value = strtolower( trim( (string) $value ) );
				return array_key_exists( $value, $field['choices'] ) ? $value : $field['invalid'];

			case 'bool':
				if ( is_bool( $value ) ) {
					return $value;
				}
				$value = strtolower( trim( (string) $value ) );
				if ( in_array( $value, array( 'true', '1', 'yes', 'on' ), true ) ) {
					return true;
				}
				if ( in_array( $value, array( 'false', '0', 'no', 'off' ), true ) ) {
					return false;
				}
				return $field['invalid'];

			case 'int':
				$value = trim( (string) $value );
				if ( ! preg_match( '/^\d+(\.\d+)?(px)?$/i', $value ) || ( 0 === (int) $value && $field['min'] > 0 ) ) {
					return $field['invalid'];
				}
				return max( $field['min'], min( $field['max'], (int) $value ) );

			case 'text':
				return self::sanitize_text( $value );

			case 'color':
				return self::sanitize_color( $value, $field['invalid'] );
		}
		return $field['invalid'];
	}

	/**
	 * Plain single-line text that is safe inside a shortcode attribute (no quotes or brackets), max 60 characters.
	 *
	 * @param mixed $value Raw text.
	 * @return string
	 */
	public static function sanitize_text( $value ) {
		$value = sanitize_text_field( (string) $value );
		$value = trim( str_replace( array( '"', "'", '[', ']' ), '', $value ) );
		return function_exists( 'mb_substr' ) ? mb_substr( $value, 0, 60 ) : substr( $value, 0, 60 );
	}

	/**
	 * Accepts hex colors, CSS color names and rgb()/rgba()/hsl()/hsla(); anything else returns `$fallback`.
	 *
	 * @param mixed  $value    Raw color.
	 * @param string $fallback Fallback color.
	 * @return string
	 */
	public static function sanitize_color( $value, $fallback ) {
		$value = trim( (string) $value );
		if ( sanitize_hex_color( $value ) ) {
			return strtolower( $value );
		}
		if ( preg_match( '/^[a-z]{3,20}$/i', $value ) ) {
			return strtolower( $value );
		}
		if ( preg_match( '/^(rgba?|hsla?)\(\s*[0-9.,%\s\/]+\)$/i', $value ) ) {
			return strtolower( $value );
		}
		return $fallback;
	}

	/**
	 * Serializes a value for a shortcode attribute.
	 *
	 * @param mixed $value Normalized value.
	 * @return string
	 */
	public static function to_string( $value ) {
		if ( is_bool( $value ) ) {
			return $value ? 'true' : 'false';
		}
		return (string) $value;
	}

	/**
	 * Builds a shortcode that emits only non-default attributes (relative to the saved table when `$id` is given).
	 *
	 * @param array      $values Normalized values.
	 * @param int        $id     Saved table id, or 0.
	 * @param array|null $base   Values the saved table already provides.
	 * @return string
	 */
	public static function to_shortcode( $values, $id = 0, $base = null ) {
		$values  = self::sanitize( $values );
		$compare = is_array( $base ) ? self::sanitize( $base ) : self::defaults();
		$parts   = array( self::SHORTCODE );
		if ( $id ) {
			$parts[] = 'id="' . absint( $id ) . '"';
		}
		foreach ( $values as $key => $value ) {
			if ( $value !== $compare[ $key ] ) {
				$parts[] = $key . '="' . self::to_string( $value ) . '"';
			}
		}
		return '[' . implode( ' ', $parts ) . ']';
	}

	/**
	 * Converts a legacy widget instance into normalized options. Empty strings (saved by
	 * old versions for untouched fields) fall back to defaults.
	 *
	 * @param array $instance Widget instance.
	 * @return array<string, mixed>
	 */
	public static function from_widget( $instance ) {
		$raw = array();
		foreach ( self::schema() as $key => $field ) {
			$widget_key = $field['widget_key'];
			if ( isset( $instance[ $widget_key ] ) && '' !== $instance[ $widget_key ] ) {
				$raw[ $key ] = $instance[ $widget_key ];
			}
		}
		return self::sanitize( $raw );
	}

	/**
	 * Converts normalized options into widget instance keys (string values, as WP_Widget stores them).
	 *
	 * @param array $values Normalized values.
	 * @return array<string, string>
	 */
	public static function to_widget( $values ) {
		$values   = self::sanitize( $values );
		$instance = array();
		foreach ( self::schema() as $key => $field ) {
			$instance[ $field['widget_key'] ] = self::to_string( $values[ $key ] );
		}
		return $instance;
	}

	/**
	 * Schema for client-side code (Builder): everything needed to render fields and mirror to_shortcode().
	 *
	 * @return array
	 */
	public static function for_js() {
		$out = array();
		foreach ( self::schema() as $key => $field ) {
			$out[ $key ] = array_intersect_key( $field, array_flip( array( 'type', 'default', 'invalid', 'min', 'max', 'choices', 'section', 'label', 'help' ) ) );
		}
		return $out;
	}
}
