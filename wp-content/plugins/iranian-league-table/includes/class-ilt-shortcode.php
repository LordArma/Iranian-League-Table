<?php
/**
 * [iran_league] shortcode.
 *
 * @package Iranian_League_Table
 */

defined( 'ABSPATH' ) || exit;

/**
 * Shortcode handler.
 */
final class ILT_Shortcode {

	/**
	 * Registers the shortcode.
	 */
	public static function register() {
		add_shortcode( ILT_Attributes::SHORTCODE, array( __CLASS__, 'render' ) );
	}

	/**
	 * Shortcode callback.
	 *
	 * @param array|string $atts    Attributes.
	 * @param string|null  $content Unused.
	 * @param string       $tag     Shortcode tag.
	 * @return string
	 */
	public static function render( $atts = array(), $content = null, $tag = '' ) {
		$atts = array_change_key_case( (array) $atts, CASE_LOWER );
		$base = self::base_values( $atts );

		$pairs = array();
		foreach ( $base as $key => $value ) {
			$pairs[ $key ] = ILT_Attributes::to_string( $value );
		}
		$values = ILT_Attributes::sanitize( shortcode_atts( $pairs, $atts, $tag ? $tag : ILT_Attributes::SHORTCODE ) );

		return self::unknown_league_notice( $atts, $values ) . ILT_Renderer::render_league( $values );
	}

	/**
	 * Unknown `league` values silently fall back to the Persian Gulf league for visitors;
	 * people who can edit posts get a short explanation above the table.
	 *
	 * @param array $atts   Lower-cased attributes.
	 * @param array $values Normalized values.
	 * @return string
	 */
	public static function unknown_league_notice( $atts, $values ) {
		if ( ! isset( $atts['league'] ) || null !== ILT_Leagues::find( $atts['league'] ) || ! current_user_can( 'edit_posts' ) ) {
			return '';
		}
		$message = sprintf(
			/* translators: 1: league value from the shortcode, 2: league shown instead, 3: list of valid values. */
			__( 'Unknown league "%1$s", so %2$s is shown. Use one of: %3$s. Only editors see this message.', 'iranian-league-table' ),
			(string) $atts['league'],
			ILT_Leagues::label( $values['league'] ),
			implode( ', ', array_keys( ILT_Leagues::all() ) )
		);
		return '<p class="ilt-editor-notice" role="note">' . esc_html( $message ) . '</p>';
	}

	/**
	 * Values the explicit attributes are applied on top of: a saved table (id="N") or the defaults.
	 *
	 * @param array $atts Lower-cased attributes.
	 * @return array
	 */
	public static function base_values( $atts ) {
		/**
		 * Filters the base values for a shortcode before explicit attributes are applied.
		 * Saved tables hook in here.
		 *
		 * @param array $values Normalized defaults.
		 * @param array $atts   Raw attributes.
		 */
		$base = apply_filters( 'ilt_shortcode_base_values', ILT_Attributes::defaults(), $atts );
		return ILT_Attributes::sanitize( $base );
	}
}
