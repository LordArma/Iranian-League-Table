<?php
/**
 * Test config for the wp-phpunit package (WP_PHPUNIT__TESTS_CONFIG). Values come from env vars.
 * The database is emptied by the test suite: never point it at a real site's database.
 */

define( 'ABSPATH', rtrim( getenv( 'ILT_WP_DIR' ) ?: '/var/www/html', '/' ) . '/' );
define( 'DB_NAME', getenv( 'ILT_DB_NAME' ) ?: 'wp_tests' );
define( 'DB_USER', getenv( 'ILT_DB_USER' ) ?: 'wp' );
define( 'DB_PASSWORD', getenv( 'ILT_DB_PASSWORD' ) ?: 'wp' );
define( 'DB_HOST', getenv( 'ILT_DB_HOST' ) ?: 'db' );
define( 'DB_CHARSET', 'utf8mb4' );
define( 'DB_COLLATE', '' );
$table_prefix = 'wptests_'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
define( 'WP_TESTS_DOMAIN', 'example.org' );
define( 'WP_TESTS_EMAIL', 'admin@example.org' );
define( 'WP_TESTS_TITLE', 'Test Blog' );
define( 'WP_PHP_BINARY', 'php' );
define( 'WPLANG', '' );
define( 'WP_DEBUG', true );
