<?php

namespace ILT\Tests;

use Iranian_League_Widget;

class ShortcodeWidgetTest extends TestCase {

	public function test_readme_examples_render() {
		$examples = array(
			array( 'league' => 'persiangulf', 'mode' => 'basic' ),
			array( 'league' => 'azadegan', 'mode' => 'advanced' ),
			array( 'league' => 'persiangulf', 'mode' => 'basic', 'logo' => 'false' ),
			array( 'league' => 'persiangulf', 'mode' => 'basic', 'logo' => 'false', 'farsi_numbers' => 'true' ),
			array( 'league' => 'kowsar', 'mode' => 'basic', 'logo' => 'false' ),
			array( 'league' => 'bartar', 'mode' => 'advanced', 'title_backcolor' => '#212121', 'title_color' => '#ffffff', 'text_color' => '#212121', 'odd_color' => '#ffffff', 'even_color' => '#eeeeee', 'logo_size' => '15', 'logo' => 'true', 'title_font' => '12', 'text_font' => '13', 'farsi_numbers' => 'false' ),
		);
		foreach ( $examples as $atts ) {
			$html = $this->shortcode( $atts );
			$this->assertStringContainsString( '<table class="ilt-standing">', $html );
			$this->assertSame( substr_count( $html, '<div' ), substr_count( $html, '</div>' ) );
		}
	}

	public function test_uppercase_attribute_names() {
		$this->assertStringContainsString( 'ilt--advanced', $this->shortcode( array( 'MODE' => 'advanced' ) ) );
	}

	public function test_path_traversal_creates_no_files() {
		$before = $this->plugin_files();
		$this->shortcode( array( 'league' => '../../../../.htaccess' ) );
		$this->shortcode( array( 'league' => '../evil.php' ) );
		$this->assertSame( $before, $this->plugin_files() );
		$this->assertFileDoesNotExist( WP_PLUGIN_DIR . '/.htaccess' );
	}

	public function test_attribute_xss_is_neutralized() {
		$html = $this->shortcode( array( 'title_backcolor' => 'red;" onmouseover="alert(1)', 'logo_size' => '1" onerror="x' ) );
		$this->assertStringNotContainsString( 'onmouseover', $html );
		$this->assertStringNotContainsString( 'onerror', $html );
	}

	public function test_unavailable_message() {
		$GLOBALS['ilt_http_mode'] = 'error';
		$this->assertStringContainsString( 'temporarily unavailable', $this->shortcode( array( 'league' => 'kowsar' ) ) );
	}

	public function test_base_values_filter_is_applied_before_explicit_attributes() {
		$cb = function ( $values ) {
			$values['mode'] = 'advanced';
			$values['logo'] = false;
			return $values;
		};
		$GLOBALS['ilt_test_filters']['ilt_shortcode_base_values'] = $cb;
		$html = $this->shortcode( array( 'logo' => 'true' ) );
		unset( $GLOBALS['ilt_test_filters']['ilt_shortcode_base_values'] );
		$this->assertStringContainsString( 'ilt--advanced', $html );
		$this->assertStringContainsString( '<img', $html );
	}

	public function test_widget_update_sanitizes() {
		$w     = new Iranian_League_Widget();
		$saved = $w->update(
			array( 'title' => '<b>Hi</b>', 'league' => '../../x', 'table_type' => 'advanced', 'title_color' => 'red;"x', 'logo_size' => '1000', 'farsi_numbers' => 'true' ),
			array()
		);
		$this->assertSame( 'Hi', $saved['title'] );
		$this->assertSame( 'persiangulf', $saved['league'] );
		$this->assertSame( '#212121', $saved['title_color'] );
		$this->assertSame( '64', $saved['logo_size'] );
		$this->assertSame( 'advanced', $saved['table_type'] );
	}

	public function test_widget_renders_old_instances() {
		$w = new Iranian_League_Widget();
		ob_start();
		$w->widget( array( 'before_widget' => '<section>', 'after_widget' => '</section>', 'before_title' => '<h2>', 'after_title' => '</h2>' ), array( 'title' => 'T<b>', 'league' => 'azadegan' ) );
		$html = ob_get_clean();
		$this->assertStringContainsString( '<h2>T&lt;b&gt;</h2>', $html );
		$this->assertStringContainsString( 'ilt--azadegan', $html );
	}

	public function test_widget_form() {
		$w = new Iranian_League_Widget();
		ob_start();
		$w->form( array() );
		$form = ob_get_clean();
		$this->assertMatchesRegularExpression( '/value="persiangulf" selected/', $form );
		$this->assertStringContainsString( 'for="widget-iranianleaguetable_widget-1-farsi_numbers"', $form );
		$this->assertStringContainsString( 'name="widget-iranianleaguetable_widget[1][font_h_size]"', $form );
	}

	private function plugin_files() {
		$out = array();
		$it  = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( WP_PLUGIN_DIR, \FilesystemIterator::SKIP_DOTS ) );
		foreach ( $it as $f ) {
			$out[] = $f->getPathname();
		}
		sort( $out );
		return $out;
	}
}
