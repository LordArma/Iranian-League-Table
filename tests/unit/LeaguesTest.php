<?php

namespace ILT\Tests;

use ILT_Leagues;

class LeaguesTest extends TestCase {

	public function aliases() {
		return array(
			array( 'bartar', 'persiangulf' ),
			array( 'persiangulf', 'persiangulf' ),
			array( 'azadegan', 'azadegan' ),
			array( '1', 'azadegan' ),
			array( 'لیگ۱', 'azadegan' ),
			array( 'آزادگان', 'azadegan' ),
			array( 'women', 'kowsar' ),
			array( 'بانوان', 'kowsar' ),
			array( ' kowsar ', 'kowsar' ),
			array( 'unknown', 'persiangulf' ),
			array( '../../wp-config.php', 'persiangulf' ),
			array( '', 'persiangulf' ),
		);
	}

	/**
	 * @dataProvider aliases
	 */
	public function test_resolve( $value, $expected ) {
		$this->assertSame( $expected, ILT_Leagues::resolve( $value ) );
	}

	public function test_find_returns_null_for_unknown() {
		$this->assertNull( ILT_Leagues::find( 'nope' ) );
	}

	public function test_api_url() {
		$this->assertSame( 'https://web-api.varzesh3.com/v1.0/developer-tools/football/leagues/54/standing', ILT_Leagues::api_url( 'kowsar' ) );
	}
}
