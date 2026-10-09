<?php

namespace ILT\Tests;

use ILT_Attributes;
use ILT_Renderer;

class RendererTest extends TestCase {

	public function snapshot_cases() {
		return array(
			'basic'           => array( 'persiangulf', array() ),
			'advanced'        => array( 'azadegan', array( 'mode' => 'advanced' ) ),
			'latin-no-logo'   => array( 'kowsar', array( 'mode' => 'advanced', 'farsi_numbers' => 'false', 'logo' => 'false' ) ),
			'custom-colors'   => array( 'persiangulf', array( 'title_backcolor' => '#003366', 'title_color' => 'white', 'odd_color' => 'rgb(250, 250, 250)', 'logo_size' => '24', 'text_font' => '16' ) ),
		);
	}

	/**
	 * Set UPDATE_SNAPSHOTS=1 to regenerate after intended markup changes.
	 *
	 * @dataProvider snapshot_cases
	 */
	public function test_snapshot( $slug, $atts ) {
		$atts['league'] = $slug;
		$html = ILT_Renderer::render( $this->fixture( $slug ), ILT_Attributes::sanitize( $atts ) );
		$file = __DIR__ . '/snapshots/' . $this->dataName() . '.html';
		if ( getenv( 'UPDATE_SNAPSHOTS' ) || ! file_exists( $file ) ) {
			file_put_contents( $file, $html . "\n" );
			$this->markTestSkipped( 'Snapshot written: ' . basename( $file ) );
		}
		$this->assertSame( file_get_contents( $file ), $html . "\n" );
	}

	public function test_structure() {
		$html = ILT_Renderer::render( $this->fixture( 'azadegan' ), ILT_Attributes::sanitize( array( 'league' => 'azadegan', 'mode' => 'advanced' ) ) );
		$this->assertSame( substr_count( $html, '<div' ), substr_count( $html, '</div>' ) );
		$this->assertSame( 1 + 16, substr_count( $html, '<tr' ) );
		$this->assertStringContainsString( 'امتیاز', $html );
		$this->assertStringNotContainsString( 'امتياز', $html );
		$this->assertStringContainsString( '<span dir="ltr">', $html );
		$this->assertStringContainsString( 'class="il-table ilt ilt--advanced ilt--azadegan"', $html );
		$this->assertStringContainsString( '--ilt-head-bg:#212121;', $html );
		$this->assertStringNotContainsString( "\n", $html );
		$this->assertTrue( wp_style_is( 'ilt-style' ) );
	}

	public function test_remote_data_is_escaped() {
		$data                    = $this->fixture( 'persiangulf' );
		$data->teams[0]->name    = '<img src=x onerror=alert(1)>';
		$data->teams[0]->logo    = 'javascript:alert(1)';
		$data->teams[0]->points  = '5<script>';
		$data->teams[1]          = 'not an object';
		$data->title             = '<b>t</b>';
		$html = ILT_Renderer::render( $data, ILT_Attributes::defaults() );
		$this->assertStringContainsString( '&lt;img src=x onerror=alert(1)&gt;', $html );
		$this->assertStringNotContainsString( '<img src=x', $html );
		$this->assertStringNotContainsString( 'javascript:', $html );
		$this->assertStringNotContainsString( '5<script', $html );
		$this->assertSame( 1, substr_count( $html, '<script' ) ); // Only the JSON-LD tag.
		$this->assertStringNotContainsString( '<b>t</b>', $html );
	}

	public function test_hidden_logos_are_not_output() {
		$html = ILT_Renderer::render( $this->fixture( 'persiangulf' ), ILT_Attributes::sanitize( array( 'logo' => 'false' ) ) );
		$this->assertStringNotContainsString( '<img', $html );
	}

	public function test_number() {
		$this->assertSame( '۱۲۳۴۵۶۷۸۹۰', ILT_Renderer::number( 1234567890, true ) );
		$this->assertSame( '-۵', ILT_Renderer::number( -5, true ) );
		$this->assertSame( '42', ILT_Renderer::number( 42, false ) );
	}

	public function test_invalid_data_renders_unavailable_message() {
		$this->assertStringContainsString( 'temporarily unavailable', ILT_Renderer::render( (object) array( 'teams' => 'x' ), ILT_Attributes::defaults() ) );
	}
}
