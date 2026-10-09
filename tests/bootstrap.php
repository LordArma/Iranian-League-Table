<?php
/**
 * Minimal WordPress stubs so the plugin can be loaded and rendered from the CLI,
 * without a WordPress install or network access. Remote requests are served from
 * tests/fixtures/{slug}.json.
 *
 * Not a replacement for real integration tests.
 */

define( 'ABSPATH', __DIR__ . '/' );
define( 'ILT_TEST_PLUGIN_DIR', dirname( __DIR__ ) . '/wp-content/plugins/iranian-league-table' );

// The plugin writes its cache inside its own directory, so run against a throwaway copy.
$GLOBALS['ilt_test_root'] = sys_get_temp_dir() . '/ilt-test-' . getmypid();
define( 'WP_PLUGIN_DIR', $GLOBALS['ilt_test_root'] );

$GLOBALS['ilt_options']      = array();
$GLOBALS['ilt_actions']      = array();
$GLOBALS['ilt_shortcodes']   = array();
$GLOBALS['ilt_http_mode']    = 'ok';   // ok | error | http500 | garbage
$GLOBALS['ilt_http_calls']   = array();
$GLOBALS['ilt_can_edit']     = false;
$GLOBALS['ilt_activation']   = array();

function ilt_test_copy_plugin() {
	$dst = WP_PLUGIN_DIR . '/iranian-league-table';
	exec( 'rm -rf ' . escapeshellarg( WP_PLUGIN_DIR ) );
	mkdir( $dst, 0777, true );
	exec( 'cp -r ' . escapeshellarg( ILT_TEST_PLUGIN_DIR ) . '/. ' . escapeshellarg( $dst ) );
	// Start from an empty cache.
	foreach ( glob( $dst . '/data/*' ) as $f ) {
		if ( basename( $f ) !== 'index.php' ) {
			unlink( $f );
		}
	}
	return $dst;
}

function ilt_test_fixture_for_url( $url ) {
	$map = array( '/6/' => 'persiangulf', '/24/' => 'azadegan', '/54/' => 'kowsar' );
	foreach ( $map as $needle => $slug ) {
		if ( strpos( $url, 'leagues' . $needle ) !== false ) {
			return file_get_contents( __DIR__ . '/fixtures/' . $slug . '.json' );
		}
	}
	return null;
}

// ---- Options -------------------------------------------------------------
function get_option( $k, $d = false ) { return array_key_exists( $k, $GLOBALS['ilt_options'] ) ? $GLOBALS['ilt_options'][ $k ] : $d; }
function update_option( $k, $v, $a = null ) { $GLOBALS['ilt_options'][ $k ] = $v; return true; }
function add_option( $k, $v = '', $d = '', $a = 'yes' ) { if ( ! array_key_exists( $k, $GLOBALS['ilt_options'] ) ) { $GLOBALS['ilt_options'][ $k ] = $v; } return true; }
function delete_option( $k ) { unset( $GLOBALS['ilt_options'][ $k ] ); return true; }

// ---- Hooks ---------------------------------------------------------------
function add_action( $h, $cb, $p = 10, $n = 1 ) { $GLOBALS['ilt_actions'][ $h ][] = $cb; return true; }
function add_filter( $h, $cb, $p = 10, $n = 1 ) { return add_action( $h, $cb, $p, $n ); }
function apply_filters( $h, $v, ...$args ) {
	return isset( $GLOBALS['ilt_test_filters'][ $h ] ) ? call_user_func( $GLOBALS['ilt_test_filters'][ $h ], $v, ...$args ) : $v;
}
function do_action( $h ) { foreach ( $GLOBALS['ilt_actions'][ $h ] ?? array() as $cb ) { call_user_func( $cb ); } }
function add_shortcode( $t, $cb ) { $GLOBALS['ilt_shortcodes'][ $t ] = $cb; }
function register_activation_hook( $f, $cb ) { $GLOBALS['ilt_activation'][] = $cb; }
function register_widget( $c ) {}
function load_plugin_textdomain() { return true; }
function add_submenu_page() { return 'hook'; }
function wp_enqueue_style( $h = '' ) { $GLOBALS['ilt_styles']['enqueued'][ $h ] = true; }
function wp_enqueue_script() {}
function current_user_can( $cap ) { return $GLOBALS['ilt_can_edit']; }

function shortcode_atts( $pairs, $atts, $shortcode = '' ) {
	$atts = (array) $atts;
	$out  = array();
	foreach ( $pairs as $name => $default ) {
		$out[ $name ] = array_key_exists( $name, $atts ) ? $atts[ $name ] : $default;
	}
	return $out;
}

function wp_parse_args( $args, $defaults = array() ) {
	return array_merge( $defaults, is_array( $args ) ? $args : array() );
}

// ---- Paths ---------------------------------------------------------------
function plugin_dir_path( $file ) { return rtrim( dirname( $file ), '/' ) . '/'; }
function plugin_basename( $file ) { return 'iranian-league-table/' . basename( $file ); }
function plugins_url( $path = '', $plugin = '' ) {
	$base = 'https://example.test/wp-content/plugins';
	if ( $plugin ) {
		$base .= '/' . basename( dirname( $plugin ) );
	}
	return $base . ( $path !== '' ? '/' . ltrim( $path, '/' ) : '' );
}
function wp_mkdir_p( $d ) { return is_dir( $d ) || mkdir( $d, 0777, true ); }

// ---- i18n / escaping / sanitizing ---------------------------------------
function __( $s, $d = 'default' ) { return $s; }
function _e( $s, $d = 'default' ) { echo $s; }
function esc_html__( $s, $d = 'default' ) { return esc_html( $s ); }
function esc_attr__( $s, $d = 'default' ) { return esc_attr( $s ); }
function esc_html_e( $s, $d = 'default' ) { echo esc_html( $s ); }
function esc_attr_e( $s, $d = 'default' ) { echo esc_attr( $s ); }
function esc_html( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES, 'UTF-8' ); }
function esc_attr( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES, 'UTF-8' ); }
function esc_url( $u ) {
	$u = (string) $u;
	if ( $u === '' || ! preg_match( '#^https?://#i', $u ) ) {
		return '';
	}
	return str_replace( array( '"', "'", '<', '>', ' ' ), array( '%22', '%27', '%3C', '%3E', '%20' ), $u );
}
function sanitize_hex_color( $c ) {
	if ( '' === $c ) {
		return '';
	}
	return preg_match( '|^#([A-Fa-f0-9]{3}){1,2}$|', $c ) ? $c : null;
}
function sanitize_text_field( $s ) { return trim( strip_tags( (string) $s ) ); }
function sanitize_key( $k ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $k ) ); }
function absint( $n ) { return abs( (int) $n ); }
function wp_strip_all_tags( $s ) { return trim( strip_tags( (string) $s ) ); }

// ---- HTTP ----------------------------------------------------------------
class WP_Error {
	public $msg;
	public function __construct( $c = '', $m = '' ) { $this->msg = $m; }
	public function get_error_message() { return $this->msg; }
}
function is_wp_error( $x ) { return $x instanceof WP_Error; }
function wp_remote_get( $url, $args = array() ) {
	$GLOBALS['ilt_http_calls'][] = $url;
	switch ( $GLOBALS['ilt_http_mode'] ) {
		case 'error':
			return new WP_Error( 'http_request_failed', 'timeout' );
		case 'http500':
			return array( 'response' => array( 'code' => 500 ), 'body' => 'oops' );
		case 'garbage':
			return array( 'response' => array( 'code' => 200 ), 'body' => '<html>not json</html>' );
	}
	if ( false !== strpos( $url, 'api.github.com' ) ) {
		return array( 'response' => array( 'code' => 200 ), 'body' => json_encode( $GLOBALS['ilt_github_release'] ?? array() ) );
	}
	$body = ilt_test_fixture_for_url( $url );
	if ( null === $body ) {
		// Logo request: return a tiny fake PNG.
		return array( 'response' => array( 'code' => 200 ), 'body' => "\x89PNG fake " . md5( $url ) );
	}
	return array( 'response' => array( 'code' => 200 ), 'body' => $body );
}
function wp_remote_retrieve_response_code( $r ) { return is_array( $r ) ? $r['response']['code'] : ''; }
function wp_remote_retrieve_body( $r ) { return is_array( $r ) ? $r['body'] : ''; }

// ---- Widgets -------------------------------------------------------------
class WP_Widget {
	public $id_base;
	public function __construct( $id_base = '', $name = '', $opts = array() ) { $this->id_base = $id_base; }
	public function get_field_id( $f ) { return 'widget-' . $this->id_base . '-1-' . $f; }
	public function get_field_name( $f ) { return 'widget-' . $this->id_base . '[1][' . $f . ']'; }
}

// ---- Misc (added for 3.2.1) ---------------------------------------------
$GLOBALS['ilt_transients'] = array();
function get_transient( $k ) { return $GLOBALS['ilt_transients'][ $k ] ?? false; }
function set_transient( $k, $v, $ttl = 0 ) { $GLOBALS['ilt_transients'][ $k ] = $v; return true; }
function delete_transient( $k ) { unset( $GLOBALS['ilt_transients'][ $k ] ); return true; }
function wp_parse_url( $u, $c = -1 ) { return parse_url( $u, $c ); }
function wp_json_encode( $d, $o = 0 ) { return json_encode( $d, $o | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ); }
function wp_safe_remote_get( $url, $args = array() ) { return wp_remote_get( $url, $args ); }
function wp_remote_retrieve_header( $r, $h ) { return is_array( $r ) ? ( $r['headers'][ $h ] ?? '' ) : ''; }

// ---- Added for 4.0.0 ------------------------------------------------------
foreach ( array( 'MINUTE_IN_SECONDS' => 60, 'HOUR_IN_SECONDS' => 3600, 'DAY_IN_SECONDS' => 86400, 'WEEK_IN_SECONDS' => 604800 ) as $ilt_c => $ilt_v ) {
	defined( $ilt_c ) || define( $ilt_c, $ilt_v );
}
$GLOBALS['ilt_cron']   = array(); // [ hook, args, timestamp, recurrence ]
$GLOBALS['ilt_styles'] = array( 'registered' => array(), 'enqueued' => array() );

function ilt_test_reset() {
	$GLOBALS['ilt_options']    = array();
	$GLOBALS['ilt_transients'] = array();
	$GLOBALS['ilt_cron']       = array();
	$GLOBALS['ilt_http_calls'] = array();
	$GLOBALS['ilt_http_mode']  = 'ok';
	$GLOBALS['ilt_can_edit']   = false;
	$GLOBALS['ilt_styles']     = array( 'registered' => array(), 'enqueued' => array() );
	$GLOBALS['ilt_posts']      = array();
	$GLOBALS['ilt_meta']       = array();
	exec( 'rm -rf ' . escapeshellarg( WP_PLUGIN_DIR . '/uploads' ) );
}

function wp_list_pluck( $list, $field ) { $o = array(); foreach ( $list as $k => $v ) { $o[ $k ] = $v[ $field ]; } return $o; }
function trailingslashit( $s ) { return rtrim( $s, '/\\' ) . '/'; }
function set_url_scheme( $u ) { return $u; }
function wp_upload_dir( $time = null, $create = true ) {
	return array( 'basedir' => WP_PLUGIN_DIR . '/uploads', 'baseurl' => 'https://example.test/wp-content/uploads' );
}
function wp_is_writable( $p ) { return is_writable( $p ); }
function wp_delete_file( $f ) { @unlink( $f ); }
function wp_generate_password( $n = 12, $s = true ) { return substr( md5( uniqid( '', true ) ), 0, $n ); }

function wp_next_scheduled( $hook, $args = array() ) {
	foreach ( $GLOBALS['ilt_cron'] as $e ) {
		if ( $e[0] === $hook && $e[1] === $args ) {
			return $e[2];
		}
	}
	return false;
}
function wp_schedule_single_event( $ts, $hook, $args = array() ) { $GLOBALS['ilt_cron'][] = array( $hook, $args, $ts, false ); return true; }
function wp_schedule_event( $ts, $rec, $hook, $args = array() ) { $GLOBALS['ilt_cron'][] = array( $hook, $args, $ts, $rec ); return true; }
function wp_clear_scheduled_hook( $hook, $args = array() ) { return wp_unschedule_hook( $hook ); }
function wp_unschedule_hook( $hook ) {
	$GLOBALS['ilt_cron'] = array_values( array_filter( $GLOBALS['ilt_cron'], function ( $e ) use ( $hook ) { return $e[0] !== $hook; } ) );
	return 1;
}
/** Runs and removes all queued single events (like a WP-Cron request). */
function ilt_test_run_cron() {
	$events = array_filter( $GLOBALS['ilt_cron'], function ( $e ) { return false === $e[3]; } );
	$GLOBALS['ilt_cron'] = array_values( array_filter( $GLOBALS['ilt_cron'], function ( $e ) { return false !== $e[3]; } ) );
	foreach ( $events as $e ) {
		foreach ( $GLOBALS['ilt_actions'][ $e[0] ] ?? array() as $cb ) {
			call_user_func_array( $cb, $e[1] );
		}
	}
}
function register_deactivation_hook( $f, $cb ) {}

function locate_template( $names ) { return ''; }
function wp_register_style( $h, $src, $deps = array(), $ver = false ) { $GLOBALS['ilt_styles']['registered'][ $h ] = $src; return true; }
function wp_style_is( $h, $list = 'enqueued' ) { return isset( $GLOBALS['ilt_styles'][ $list ][ $h ] ); }
function has_shortcode( $c, $t ) { return false !== strpos( $c, '[' . $t ); }
function is_active_widget() { return false; }
function selected( $a, $b = true, $echo = true ) {
	$r = ( (string) $a === (string) $b ) ? ' selected=\'selected\'' : '';
	if ( $echo ) { echo $r; }
	return $r;
}
function shortcode_parse_atts( $text ) {
	$atts = array();
	if ( preg_match_all( '/([\w-]+)\s*=\s*"([^"]*)"/', $text, $m, PREG_SET_ORDER ) ) {
		foreach ( $m as $x ) { $atts[ strtolower( $x[1] ) ] = stripcslashes( $x[2] ); }
	}
	return $atts;
}

// ---- Added for 4.1.0: posts (in memory) -----------------------------------
$GLOBALS['ilt_posts'] = array();
$GLOBALS['ilt_meta']  = array();
function is_admin() { return false; }
function register_post_type( $t, $a = array() ) {}
function wp_slash( $v ) { return $v; }
function get_post( $p = null ) {
	$id = is_object( $p ) ? $p->ID : (int) $p;
	return $GLOBALS['ilt_posts'][ $id ] ?? null;
}
function wp_insert_post( $post, $wp_error = false ) {
	$id = count( $GLOBALS['ilt_posts'] ) + 100;
	$GLOBALS['ilt_posts'][ $id ] = (object) array_merge( array( 'ID' => $id, 'post_content' => '' ), $post, array( 'ID' => $id ) );
	return $id;
}
function wp_update_post( $post, $wp_error = false ) {
	$id = (int) $post['ID'];
	foreach ( $post as $k => $v ) { $GLOBALS['ilt_posts'][ $id ]->$k = $v; }
	return $id;
}
function wp_delete_post( $id, $force = false ) {
	$p = get_post( $id );
	unset( $GLOBALS['ilt_posts'][ $id ], $GLOBALS['ilt_meta'][ $id ] );
	return $p;
}
function get_post_meta( $id, $k = '', $single = false ) { return $GLOBALS['ilt_meta'][ $id ][ $k ] ?? ''; }
function update_post_meta( $id, $k, $v ) { $GLOBALS['ilt_meta'][ $id ][ $k ] = $v; return true; }
function get_posts( $args ) {
	$out = array();
	foreach ( array_reverse( $GLOBALS['ilt_posts'], true ) as $id => $p ) {
		if ( $p->post_type === $args['post_type'] && ( 'any' === ( $args['post_status'] ?? 'publish' ) || $p->post_status === ( $args['post_status'] ?? 'publish' ) ) ) {
			$out[] = $id;
		}
	}
	return $out;
}

// ---- Added for 4.3.0 ------------------------------------------------------
function wp_date( $format, $ts = null ) { return gmdate( $format, null === $ts ? time() : $ts ); }

// ---- Added for 4.4.0 ------------------------------------------------------
function get_site_transient( $k ) { return get_transient( 'site_' . $k ); }
function set_site_transient( $k, $v, $t = 0 ) { return set_transient( 'site_' . $k, $v, $t ); }
function delete_site_transient( $k ) { return delete_transient( 'site_' . $k ); }
