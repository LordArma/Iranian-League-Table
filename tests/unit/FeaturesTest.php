<?php

namespace ILT\Tests;

use ILT_Attributes;
use ILT_Jalali;
use ILT_Renderer;

class FeaturesTest extends TestCase {

	private function render( $atts, $slug = 'persiangulf', $meta = array() ) {
		$atts['league'] = $slug;
		return ILT_Renderer::render( $this->fixture( $slug ), ILT_Attributes::sanitize( $atts ), $meta );
	}

	private function rows( $html ) {
		preg_match_all( '#<tr( class="([^"]*)")?><td class="ilt-rank">([^<]*)</td><th scope="row" class="ilt-team">.*?class="ilt-team-name"[^>]*>([^<]*)<#u', $html, $m, PREG_SET_ORDER );
		return array_map(
			function ( $x ) {
				return array( 'class' => $x[2], 'rank' => $x[3], 'name' => $x[4] );
			},
			$m
		);
	}

	public function test_zones_and_legend() {
		$html = $this->render( array( 'highlight_top' => '2', 'highlight_bottom' => '3', 'farsi_numbers' => 'false' ) );
		$rows = $this->rows( $html );
		$this->assertCount( 18, $rows );
		$this->assertSame( array( 'ilt-row--top', 'ilt-row--top', '' ), array_column( array_slice( $rows, 0, 3 ), 'class' ) );
		$this->assertSame( array( '', 'ilt-row--bottom', 'ilt-row--bottom', 'ilt-row--bottom' ), array_column( array_slice( $rows, 14 ), 'class' ) );
		$this->assertStringContainsString( '--ilt-top:#dff3e4;--ilt-bottom:#fbe3e3;', $html );
		$this->assertStringContainsString( 'ilt-legend__item--top"><span class="ilt-legend__swatch" aria-hidden="true"></span>سهمیه آسیایی</li>', $html );
		$this->assertStringContainsString( 'سقوط به لیگ یک', $html );

		$custom = $this->render( array( 'highlight_top' => '1', 'top_label' => 'قهرمان' ) );
		$this->assertStringContainsString( '</span>قهرمان</li>', $custom );
		$this->assertStringNotContainsString( 'ilt-legend__item--bottom', $custom );
	}

	public function test_default_output_has_no_feature_markup() {
		$html = $this->render( array() );
		foreach ( array( 'ilt-legend', 'ilt-footer', 'ilt-row--', '--ilt-top', '--ilt-highlight', 'ilt--auto-dark', '<a ' ) as $needle ) {
			$this->assertStringNotContainsString( $needle, $html );
		}
	}

	public function test_highlight_matches_arabic_letters_and_spacing() {
		$html = $this->render( array( 'highlight' => ' پرسپوليس ' ) ); // Arabic ye.
		$rows = $this->rows( $html );
		$hit  = array_values( array_filter( $rows, function ( $r ) { return false !== strpos( $r['class'], 'ilt-row--highlight' ); } ) );
		$this->assertCount( 1, $hit );
		$this->assertSame( 'پرسپولیس', $hit[0]['name'] );
		$this->assertStringContainsString( '--ilt-highlight:#fff1b8;', $html );
	}

	public function test_rows_limit_and_around() {
		$this->assertCount( 5, $this->rows( $this->render( array( 'rows' => '5' ) ) ) );

		$around = $this->rows( $this->render( array( 'around' => 'سپاهان', 'farsi_numbers' => 'false' ) ) );
		$this->assertSame( array( '2', '3', '4', '5', '6' ), array_column( $around, 'rank' ) );

		$first = $this->rows( $this->render( array( 'around' => 'تراکتور', 'farsi_numbers' => 'false' ) ) );
		$this->assertSame( array( '1', '2', '3', '4', '5' ), array_column( $first, 'rank' ), 'window shifts at the top edge' );

		$fallback = $this->rows( $this->render( array( 'around' => 'Unknown FC', 'rows' => '3' ) ) );
		$this->assertCount( 3, $fallback, 'unknown team falls back to rows' );
	}

	public function test_team_links_only_to_varzesh3() {
		$data                  = $this->fixture( 'persiangulf' );
		$data->teams[0]->link  = 'https://evil.example/x';
		$data->teams[1]->link  = 'javascript:alert(1)';
		$html                  = ILT_Renderer::render( $data, ILT_Attributes::sanitize( array( 'team_links' => 'true' ) ) );
		$this->assertSame( 16, substr_count( $html, '<a class="ilt-team-name" href="https://www.varzesh3.com/' ) );
		$this->assertStringNotContainsString( 'evil.example', $html );
		$this->assertStringNotContainsString( 'javascript:', $html );
		$this->assertStringContainsString( 'rel="noopener"', $html );
	}

	public function test_footer_updated_and_source() {
		$ts   = gmmktime( 3, 40, 0, 10, 9, 2026 );
		$html = $this->render( array( 'show_updated' => 'true', 'show_source' => 'true' ), 'persiangulf', array( 'updated' => $ts ) );
		$this->assertStringContainsString( 'به‌روزرسانی: ۱۷ مهر ۱۴۰۵، ساعت ۰۳:۴۰', $html );
		$this->assertStringContainsString( 'منبع: <a href="https://www.varzesh3.com/football/league/6/', $html );
		$this->assertStringNotContainsString( 'ilt-updated', $this->render( array( 'show_updated' => 'true' ) ), 'no date when unknown' );
	}

	public function jalali_dates() {
		return array(
			array( 2025, 3, 21, array( 1404, 1, 1 ) ),
			array( 2026, 10, 9, array( 1405, 7, 17 ) ),
			array( 2024, 3, 19, array( 1402, 12, 29 ) ),
			array( 2025, 3, 20, array( 1403, 12, 30 ) ), // 1403 is a leap year.
			array( 2000, 1, 1, array( 1378, 10, 11 ) ),
		);
	}

	/**
	 * @dataProvider jalali_dates
	 */
	public function test_jalali( $y, $m, $d, $expected ) {
		$this->assertSame( $expected, ILT_Jalali::from_gregorian( $y, $m, $d ) );
	}

	public function test_dark_mode_class() {
		$this->assertStringContainsString( 'ilt--auto-dark', $this->render( array( 'dark_mode' => 'true' ) ) );
	}

	public function test_structured_data_can_be_turned_off() {
		update_option( 'ilt_settings', array( 'structured_data' => false ) );
		$this->assertStringNotContainsString( 'ld+json', $this->render( array() ) );
	}

	public function test_structured_data_on_by_default() {
		$html = $this->render( array() );
		$this->assertMatchesRegularExpression( '#<script type="application/ld\+json">\{.*"@type":"SportsOrganization".*\}</script>$#u', $html );
		preg_match( '#<script type="application/ld\+json">(.*)</script>#u', $html, $m );
		$json = json_decode( $m[1], true );
		$this->assertCount( 18, $json['member'] );
		$this->assertSame( 'SportsTeam', $json['member'][0]['@type'] );
	}
}
