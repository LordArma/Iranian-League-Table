<?php

namespace ILT\Tests;

use ILT_Attributes;
use ILT_Presets;
use ILT_Tables;

class TablesPresetsTest extends TestCase {

	public function test_presets_only_set_colors() {
		$presets = ILT_Presets::all();
		$this->assertArrayHasKey( 'dark', $presets );
		foreach ( $presets as $preset ) {
			$this->assertSame( array( 'title_backcolor', 'title_color', 'text_color', 'odd_color', 'even_color' ), array_keys( $preset['values'] ) );
		}
		$this->assertSame( array_intersect_key( ILT_Attributes::defaults(), $presets['classic']['values'] ), $presets['classic']['values'] );
	}

	public function test_save_sanitizes_and_get_returns_values() {
		$id    = ILT_Tables::save( '<b>Sidebar</b>', array( 'league' => 'women', 'mode' => 'advanced', 'odd_color' => 'red;"x' ) );
		$table = ILT_Tables::get( $id );
		$this->assertSame( 'Sidebar', $table['title'] );
		$this->assertSame( 'kowsar', $table['values']['league'] );
		$this->assertSame( '#ffffff', $table['values']['odd_color'] );
	}

	public function test_empty_name_defaults_to_league_label() {
		$this->assertSame( 'League One', ILT_Tables::get( ILT_Tables::save( '', array( 'league' => 'azadegan' ) ) )['title'] );
	}

	public function test_update_and_duplicate_and_delete() {
		$id = ILT_Tables::save( 'A', array() );
		ILT_Tables::save( 'A2', array( 'mode' => 'advanced' ), $id );
		$this->assertSame( 'advanced', ILT_Tables::get( $id )['values']['mode'] );
		$this->assertInstanceOf( 'WP_Error', ILT_Tables::save( 'x', array(), 99999 ) );

		$copy = ILT_Tables::duplicate( $id );
		$this->assertSame( 'A2 (copy)', ILT_Tables::get( $copy )['title'] );
		$this->assertCount( 2, ILT_Tables::all() );

		$this->assertTrue( ILT_Tables::delete( $id ) );
		$this->assertNull( ILT_Tables::get( $id ) );
		$this->assertFalse( ILT_Tables::delete( $id ) );
	}

	public function test_other_post_types_are_not_tables() {
		$id = wp_insert_post( array( 'post_type' => 'post', 'post_status' => 'publish', 'post_title' => 'x' ) );
		$this->assertNull( ILT_Tables::get( $id ) );
	}

	public function test_shortcode_id_uses_saved_values_and_attributes_override() {
		$GLOBALS['ilt_test_filters']['ilt_shortcode_base_values'] = array( 'ILT_Tables', 'shortcode_base_values' );
		$id = ILT_Tables::save( 'T', array( 'league' => 'azadegan', 'mode' => 'advanced', 'title_backcolor' => '#003366' ) );

		$html = $this->shortcode( array( 'id' => (string) $id ) );
		$this->assertStringContainsString( 'ilt--advanced ilt--azadegan', $html );
		$this->assertStringContainsString( '--ilt-head-bg:#003366;', $html );

		$html = $this->shortcode( array( 'id' => (string) $id, 'mode' => 'basic' ) );
		$this->assertStringContainsString( 'ilt--basic ilt--azadegan', $html );

		// Unknown id: defaults.
		$html = $this->shortcode( array( 'id' => '424242' ) );
		$this->assertStringContainsString( 'ilt--basic ilt--persiangulf', $html );
		unset( $GLOBALS['ilt_test_filters']['ilt_shortcode_base_values'] );
	}

	public function test_saved_table_shortcode_is_short() {
		$values = ILT_Attributes::sanitize( array( 'league' => 'kowsar', 'mode' => 'advanced' ) );
		$this->assertSame( '[iran_league id="12"]', ILT_Attributes::to_shortcode( $values, 12, $values ) );
	}
}
