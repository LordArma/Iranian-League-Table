<?php
/**
 * Integration tests: real WordPress + the WordPress PHPUnit test library.
 *
 * - With wp-env:  npm run env:start && npm run test:integration   (WP_TESTS_DIR is set by wp-env)
 * - Elsewhere:    WP_TESTS_DIR=/path/to/wordpress-phpunit and, for the wp-phpunit package,
 *                 WP_PHPUNIT__TESTS_CONFIG=tests/integration/wp-tests-config.php (see that file for the env vars).
 */

$ilt_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $ilt_tests_dir ) {
	$ilt_tests_dir = '/wordpress-phpunit';
}
if ( ! file_exists( $ilt_tests_dir . '/includes/functions.php' ) ) {
	fwrite( STDERR, "WordPress test library not found in $ilt_tests_dir (set WP_TESTS_DIR).\n" );
	exit( 1 );
}

define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', dirname( __DIR__, 2 ) . '/vendor/yoast/phpunit-polyfills' );
define( 'ILT_TEST_FIXTURES', dirname( __DIR__ ) . '/fixtures' );
define( 'ILT_TEST_UPLOADS', sys_get_temp_dir() . '/ilt-it-uploads' );
if ( ! is_dir( ILT_TEST_UPLOADS ) ) {
	mkdir( ILT_TEST_UPLOADS, 0777, true );
}

require_once $ilt_tests_dir . '/includes/functions.php';

tests_add_filter(
	'muplugins_loaded',
	function () {
		// Keep test logos out of the real uploads folder.
		add_filter(
			'upload_dir',
			function ( $dirs ) {
				$dirs['basedir'] = ILT_TEST_UPLOADS;
				$dirs['path']    = ILT_TEST_UPLOADS;
				$dirs['baseurl'] = 'http://example.org/ilt-it-uploads';
				return $dirs;
			}
		);
		require WP_PLUGIN_DIR . '/iranian-league-table/iranianleaguetable.php';
	}
);

require $ilt_tests_dir . '/includes/bootstrap.php';
