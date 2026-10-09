<?php
/**
 * PHPUnit bootstrap: WordPress stubs + a throwaway copy of the plugin.
 */

require dirname( __DIR__ ) . '/bootstrap.php';
require dirname( __DIR__, 2 ) . '/vendor/autoload.php';

$GLOBALS['ilt_test_plugin_dir'] = ilt_test_copy_plugin();
require $GLOBALS['ilt_test_plugin_dir'] . '/iranianleaguetable.php';
require __DIR__ . '/TestCase.php';

register_shutdown_function(
	function () {
		exec( 'rm -rf ' . escapeshellarg( WP_PLUGIN_DIR ) );
	}
);
