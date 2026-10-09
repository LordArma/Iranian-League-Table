<?php

namespace ILT\Tests;

use ILT_Attributes;

class AttributesTest extends TestCase {

	public function test_defaults() {
		$this->assertSame(
			array(
				'league'          => 'persiangulf',
				'mode'            => 'basic',
				'logo'            => true,
				'farsi_numbers'   => true,
				'title_backcolor' => '#212121',
				'title_color'     => '#eeeeee',
				'text_color'      => '#212121',
				'odd_color'       => '#ffffff',
				'even_color'      => '#eeeeee',
				'logo_size'       => 15,
				'title_font'      => 13,
				'text_font'       => 14,
				'rows'             => 0,
				'around'           => '',
				'highlight'        => '',
				'team_links'       => false,
				'show_updated'     => false,
				'show_source'      => false,
				'highlight_top'    => 0,
				'top_label'        => '',
				'highlight_bottom' => 0,
				'bottom_label'     => '',
				'top_color'        => '#dff3e4',
				'bottom_color'     => '#fbe3e3',
				'highlight_color'  => '#fff1b8',
				'dark_mode'        => false,
			),
			ILT_Attributes::defaults()
		);
	}

	public function test_malicious_values_are_neutralized() {
		$v = ILT_Attributes::sanitize(
			array(
				'title_backcolor' => 'red;" onmouseover="alert(1)',
				'title_color'     => '"><script>alert(1)</script>',
				'text_color'      => 'expression(alert(1))',
				'odd_color'       => 'url(javascript:alert(1))',
				'logo_size'       => '15px;" onerror="alert(1)',
				'mode'            => '<b>',
				'league'          => '../../../../.htaccess',
				'logo'            => array( 'x' ),
			)
		);
		$this->assertSame( '#212121', $v['title_backcolor'] );
		$this->assertSame( '#eeeeee', $v['title_color'] );
		$this->assertSame( '#212121', $v['text_color'] );
		$this->assertSame( '#ffffff', $v['odd_color'] );
		$this->assertSame( 15, $v['logo_size'] );
		$this->assertSame( 'advanced', $v['mode'] );
		$this->assertSame( 'persiangulf', $v['league'] );
		$this->assertTrue( $v['logo'] );
	}

	public function test_colors() {
		$this->assertSame( '#abc', ILT_Attributes::sanitize_color( '#ABC', 'x' ) );
		$this->assertSame( 'red', ILT_Attributes::sanitize_color( 'Red', 'x' ) );
		$this->assertSame( 'rgba(1, 2, 3, .5)', ILT_Attributes::sanitize_color( 'rgba(1, 2, 3, .5)', 'x' ) );
		$this->assertSame( 'x', ILT_Attributes::sanitize_color( 'rgb(1,2,3);color:red', 'x' ) );
	}

	public function test_sizes_are_clamped() {
		$v = ILT_Attributes::sanitize( array( 'title_font' => '9999', 'text_font' => '2', 'logo_size' => '20px' ) );
		$this->assertSame( 64, $v['title_font'] );
		$this->assertSame( 8, $v['text_font'] );
		$this->assertSame( 20, $v['logo_size'] );
		$this->assertSame( 14, ILT_Attributes::sanitize( array( 'text_font' => '-5' ) )['text_font'] );
	}

	public function test_legacy_boolean_semantics() {
		// Only "false" ever hid logos; only "true" ever enabled Persian digits.
		$this->assertTrue( ILT_Attributes::sanitize( array( 'logo' => 'whatever' ) )['logo'] );
		$this->assertFalse( ILT_Attributes::sanitize( array( 'logo' => 'false' ) )['logo'] );
		$this->assertFalse( ILT_Attributes::sanitize( array( 'farsi_numbers' => 'whatever' ) )['farsi_numbers'] );
		$this->assertTrue( ILT_Attributes::sanitize( array( 'farsi_numbers' => 'TRUE' ) )['farsi_numbers'] );
	}

	public function test_mode_legacy_semantics() {
		$this->assertSame( 'basic', ILT_Attributes::sanitize( array( 'mode' => 'basic' ) )['mode'] );
		$this->assertSame( 'advanced', ILT_Attributes::sanitize( array( 'mode' => 'detailed' ) )['mode'] );
	}

	public function test_to_shortcode_emits_only_non_defaults() {
		$this->assertSame( '[iran_league]', ILT_Attributes::to_shortcode( array() ) );
		$this->assertSame(
			'[iran_league league="azadegan" mode="advanced" farsi_numbers="false" title_font="12"]',
			ILT_Attributes::to_shortcode( array( 'league' => 'one', 'mode' => 'advanced', 'farsi_numbers' => 'false', 'title_font' => '12' ) )
		);
		$this->assertSame( '[iran_league id="7" mode="basic"]', ILT_Attributes::to_shortcode( array( 'mode' => 'basic' ), 7, array( 'mode' => 'advanced' ) ) );
	}

	public function test_to_shortcode_round_trip() {
		$values = ILT_Attributes::sanitize(
			array( 'league' => 'kowsar', 'mode' => 'advanced', 'logo' => 'false', 'odd_color' => '#123456', 'logo_size' => '30', 'title_color' => 'rgb(1, 2, 3)' )
		);
		$parsed = shortcode_parse_atts( trim( ILT_Attributes::to_shortcode( $values ), '[]' ) );
		$this->assertSame( $values, ILT_Attributes::sanitize( $parsed ) );
	}

	public function test_widget_mapping() {
		$instance = array( 'league' => 'azadegan', 'table_type' => 'advanced', 'title_color' => '#000000', 'title_text_color' => '#ffffff', 'font_h_size' => '20', 'logo' => '' );
		$values   = ILT_Attributes::from_widget( $instance );
		$this->assertSame( 'azadegan', $values['league'] );
		$this->assertSame( 'advanced', $values['mode'] );
		$this->assertSame( '#000000', $values['title_backcolor'] );
		$this->assertSame( '#ffffff', $values['title_color'] );
		$this->assertSame( 20, $values['title_font'] );
		$this->assertTrue( $values['logo'], 'empty strings saved by old versions fall back to defaults' );
		$this->assertSame( '#000000', ILT_Attributes::to_widget( $values )['title_color'] );
		$this->assertSame( 'true', ILT_Attributes::to_widget( $values )['logo'] );
	}

	public function test_widget_default_url_league_maps_to_persian_gulf() {
		$values = ILT_Attributes::from_widget( array( 'league' => 'https://web-api.varzesh3.com/v1.0/developer-tools/football/leagues/6/standing' ) );
		$this->assertSame( 'persiangulf', $values['league'] );
	}

	public function test_text_values_are_safe_for_shortcodes() {
		$v = ILT_Attributes::sanitize( array( 'highlight' => ' <b>پرسپولیس</b>"] [x ', 'top_label' => str_repeat( 'ا', 100 ) ) );
		$this->assertSame( 'پرسپولیس x', $v['highlight'] );
		$this->assertSame( 60, mb_strlen( $v['top_label'] ) );
		$code = ILT_Attributes::to_shortcode( $v );
		$this->assertSame( $v, ILT_Attributes::sanitize( shortcode_parse_atts( trim( $code, '[]' ) ) ) );
	}

	public function test_zero_is_valid_for_optional_counts() {
		$v = ILT_Attributes::sanitize( array( 'rows' => '0', 'highlight_top' => '3', 'highlight_bottom' => '99' ) );
		$this->assertSame( 0, $v['rows'] );
		$this->assertSame( 3, $v['highlight_top'] );
		$this->assertSame( 10, $v['highlight_bottom'] );
	}
}
