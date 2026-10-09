<?php

namespace ILT\Tests;

use Yoast\PHPUnitPolyfills\TestCases\TestCase as PolyfillTestCase;

abstract class TestCase extends PolyfillTestCase {

	protected function set_up() {
		parent::set_up();
		ilt_test_reset();
	}

	protected function fixture( $slug ) {
		return json_decode( file_get_contents( dirname( __DIR__ ) . '/fixtures/' . $slug . '.json' ), false );
	}

	protected function shortcode( $atts = array() ) {
		return call_user_func( $GLOBALS['ilt_shortcodes']['iran_league'], $atts, null, 'iran_league' );
	}

	protected function seed_last_good( $slug, $data = null ) {
		update_option( 'ilt_last_good_' . $slug, array( 'time' => time() - 1000, 'data' => $data ? $data : $this->fixture( $slug ) ) );
	}

	protected function http_calls_matching( $pattern ) {
		return count( preg_grep( $pattern, $GLOBALS['ilt_http_calls'] ) );
	}
}
