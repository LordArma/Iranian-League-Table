<?php

namespace ILT\Tests;

use ILT_Api;
use ILT_Logos;

class LogosTest extends TestCase {

	public function test_rendering_never_downloads_logos() {
		$this->shortcode( array( 'league' => 'azadegan' ) );
		$this->assertSame( 0, $this->http_calls_matching( '#static\.varzesh3#' ) );
		$this->assertNotFalse( wp_next_scheduled( ILT_Logos::SYNC_HOOK, array( 'azadegan' ) ) );
	}

	public function test_sync_downloads_into_uploads_with_hashed_names() {
		ILT_Api::refresh( 'azadegan' );
		$count = ILT_Logos::sync( 'azadegan' );
		$this->assertGreaterThan( 0, $count );
		$names = array_map( 'basename', glob( ILT_Logos::dir() . 'logo-*' ) );
		$this->assertCount( $count, preg_grep( '/^logo-[0-9a-f]{32}\.(png|jpe?g|gif|webp)$/', $names ) );
		$this->assertFileExists( ILT_Logos::dir() . 'index.php' );

		// Second sync: everything is fresh.
		$GLOBALS['ilt_http_calls'] = array();
		$this->assertSame( 0, ILT_Logos::sync( 'azadegan' ) );
	}

	public function test_url_prefers_local_copy() {
		ILT_Api::refresh( 'kowsar' );
		$remote = ILT_Api::last_good( 'kowsar' )['data']->teams[0]->logo;
		$this->assertSame( $remote, ILT_Logos::url( $remote ) );
		ILT_Logos::sync( 'kowsar' );
		$this->assertSame( 'https://example.test/wp-content/uploads/iranian-league-table/' . ILT_Logos::filename( $remote ), ILT_Logos::url( $remote ) );
	}

	public function test_non_http_urls_are_dropped() {
		$this->assertSame( '', ILT_Logos::url( 'javascript:alert(1)' ) );
		$this->assertSame( '', ILT_Logos::url( 'https://x/a b"onerror=1' ) );
		$this->assertSame( '', ILT_Logos::url( null ) );
	}

	public function test_failed_download_writes_nothing() {
		ILT_Logos::ensure_dir();
		$GLOBALS['ilt_http_mode'] = 'http500';
		$this->assertFalse( ILT_Logos::download( 'https://static.varzesh3.com/x.png', ILT_Logos::dir() . 'logo-x.png' ) );
		$this->assertSame( array( 'index.php' ), array_map( 'basename', glob( ILT_Logos::dir() . '*' ) ) );
	}
}
