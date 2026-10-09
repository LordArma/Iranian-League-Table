<?php

namespace ILT\Tests;

use ILT_Api;

class ApiTest extends TestCase {

	public function test_cold_cache_fetches_synchronously() {
		$data = ILT_Api::get_standings( 'azadegan' );
		$this->assertTrue( ILT_Api::is_valid( $data ) );
		$this->assertSame( 1, $this->http_calls_matching( '#/leagues/24/#' ) );
		$this->assertNotFalse( get_transient( 'ilt_standings_azadegan' ) );
		$this->assertNotNull( ILT_Api::last_good( 'azadegan' ) );
	}

	public function test_fresh_transient_makes_no_request() {
		ILT_Api::get_standings( 'azadegan' );
		$GLOBALS['ilt_http_calls'] = array();
		ILT_Api::get_standings( 'azadegan' );
		$this->assertSame( array(), $GLOBALS['ilt_http_calls'] );
	}

	public function test_expired_cache_serves_stale_and_refreshes_in_background() {
		$this->seed_last_good( 'kowsar' );
		$data = ILT_Api::get_standings( 'kowsar' );
		$this->assertTrue( ILT_Api::is_valid( $data ) );
		$this->assertSame( array(), $GLOBALS['ilt_http_calls'], 'a page view must not wait on the API when a copy exists' );
		$this->assertNotFalse( wp_next_scheduled( ILT_Api::REFRESH_HOOK, array( 'kowsar' ) ) );

		// Many views schedule only one refresh.
		ILT_Api::get_standings( 'kowsar' );
		$this->assertCount( 1, array_filter( $GLOBALS['ilt_cron'], function ( $e ) { return ILT_Api::REFRESH_HOOK === $e[0]; } ) );

		ilt_test_run_cron();
		$this->assertSame( 1, $this->http_calls_matching( '#/leagues/54/#' ) );
		$this->assertGreaterThan( time() - 5, ILT_Api::last_good( 'kowsar' )['time'] );
	}

	public function failure_modes() {
		return array( array( 'error' ), array( 'http500' ), array( 'garbage' ) );
	}

	/**
	 * @dataProvider failure_modes
	 */
	public function test_failure_without_cache_returns_null( $mode ) {
		$GLOBALS['ilt_http_mode'] = $mode;
		$this->assertNull( ILT_Api::get_standings( 'kowsar' ) );
		$this->assertNull( ILT_Api::last_good( 'kowsar' ) );
		$this->assertNotNull( ILT_Api::last_error( 'kowsar' ) );
	}

	/**
	 * @dataProvider failure_modes
	 */
	public function test_failure_keeps_last_good_and_backs_off( $mode ) {
		$this->seed_last_good( 'kowsar' );
		$GLOBALS['ilt_http_mode'] = $mode;
		$this->assertFalse( ILT_Api::refresh( 'kowsar' ) );
		$this->assertNotNull( ILT_Api::last_good( 'kowsar' ) );
		$this->assertNotFalse( get_transient( 'ilt_standings_kowsar' ), 'backoff copy prevents retrying on every view' );
	}

	public function test_lock_prevents_parallel_fetches() {
		set_transient( 'ilt_lock_kowsar', 1, 30 );
		$this->assertFalse( ILT_Api::refresh( 'kowsar' ) );
		$this->assertSame( array(), $GLOBALS['ilt_http_calls'] );
	}

	public function test_leagues_refresh_independently() {
		ILT_Api::get_standings( 'persiangulf' );
		$this->seed_last_good( 'azadegan' );
		ILT_Api::get_standings( 'azadegan' );
		$this->assertNotFalse( wp_next_scheduled( ILT_Api::REFRESH_HOOK, array( 'azadegan' ) ) );
	}

	public function test_refresh_all_only_touches_used_leagues() {
		ILT_Api::track_usage( 'kowsar' );
		ILT_Api::refresh_all();
		$this->assertSame( 1, count( $GLOBALS['ilt_http_calls'] ) );
		$this->assertSame( 1, $this->http_calls_matching( '#/leagues/54/#' ) );
	}

	public function test_ttl_is_clamped() {
		$this->assertSame( 300, ILT_Api::ttl() );
		update_option( 'ilt_settings', array( 'cache_ttl' => 5 ) );
		$this->assertSame( 60, ILT_Api::ttl() );
		update_option( 'ilt_settings', array( 'cache_ttl' => 999999 ) );
		$this->assertSame( 86400, ILT_Api::ttl() );
	}
}
