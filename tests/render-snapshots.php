<?php
/**
 * Renders a set of shortcode/widget cases to HTML files for before/after diffing.
 *
 * Usage: php tests/render-snapshots.php <out_dir> [--seed-legacy]
 *
 * --seed-legacy pre-fills the 3.2.0-style cache (data/<league attr> + data/<team name>.png)
 * so the original code renders from fixtures without touching the network.
 */

require __DIR__ . '/bootstrap.php';

$out_dir = $argv[1] ?? null;
if ( ! $out_dir ) {
	fwrite( STDERR, "usage: php tests/render-snapshots.php <out_dir> [--seed-legacy]\n" );
	exit( 1 );
}
@mkdir( $out_dir, 0777, true );
$seed_legacy = in_array( '--seed-legacy', $argv, true );

$cases = array(
	'readme-basic'          => array( 'league' => 'persiangulf', 'mode' => 'basic' ),
	'readme-azadegan-adv'   => array( 'league' => 'azadegan', 'mode' => 'advanced' ),
	'readme-nologo'         => array( 'league' => 'persiangulf', 'mode' => 'basic', 'logo' => 'false' ),
	'readme-nologo-fa'      => array( 'league' => 'persiangulf', 'mode' => 'basic', 'logo' => 'false', 'farsi_numbers' => 'true' ),
	'readme-custom'         => array( 'league' => 'bartar', 'mode' => 'advanced', 'title_backcolor' => '#212121', 'title_color' => '#ffffff', 'text_color' => '#212121', 'odd_color' => '#ffffff', 'even_color' => '#eeeeee', 'logo_size' => '15', 'logo' => 'true', 'title_font' => '12', 'text_font' => '13', 'farsi_numbers' => 'false' ),
	'defaults'              => array(),
	'kowsar-adv'            => array( 'league' => 'kowsar', 'mode' => 'advanced' ),
	'alias-fa-azadegan'     => array( 'league' => 'آزادگان' ),
	'alias-women'           => array( 'league' => 'women', 'mode' => 'advanced', 'farsi_numbers' => 'false' ),
);

$plugin_dir = ilt_test_copy_plugin();

if ( $seed_legacy ) {
	$fixture_of = function ( $league ) {
		$az = array( 'one', '1', 'yek', 'lige1', 'leagueone', 'azadegan', 'ligeyek', 'لیگ۱', 'آزادگان' );
		$kw = array( 'kowsar', 'kosar', 'women', 'woman', 'zanan', 'banovan', 'leaguekosar', 'زنان', 'بانو', 'بانوان' );
		return in_array( $league, $az, true ) ? 'azadegan' : ( in_array( $league, $kw, true ) ? 'kowsar' : 'persiangulf' );
	};
	foreach ( $cases as $atts ) {
		$league = $atts['league'] ?? 'bartar';
		$json   = file_get_contents( __DIR__ . '/fixtures/' . $fixture_of( $league ) . '.json' );
		file_put_contents( $plugin_dir . '/data/' . $league, $json );
		foreach ( json_decode( $json )->teams as $team ) {
			touch( $plugin_dir . '/data/' . $team->name . '.png' );
		}
	}
	$GLOBALS['ilt_options']['ilt_last_update'] = time();
	$GLOBALS['ilt_options']['ilt_last_logo']   = time();
}

require $plugin_dir . '/iranianleaguetable.php';
foreach ( $GLOBALS['ilt_activation'] as $cb ) {
	call_user_func( $cb );
}
do_action( 'plugins_loaded' );
do_action( 'init' );

$shortcode = $GLOBALS['ilt_shortcodes']['iran_league'];
foreach ( $cases as $name => $atts ) {
	file_put_contents( "$out_dir/$name.html", call_user_func( $shortcode, $atts, null, 'iran_league' ) . "\n" );
}

$widget_args = array( 'before_widget' => '<section>', 'after_widget' => '</section>', 'before_title' => '<h2>', 'after_title' => '</h2>' );
$widget      = new Iranian_League_Widget();
ob_start();
$widget->widget( $widget_args, array( 'title' => 'لیگ برتر', 'league' => 'persiangulf', 'table_type' => 'advanced', 'title_color' => '#212121', 'title_text_color' => '#ffffff', 'logo' => 'true', 'logo_size' => '15', 'font_h_size' => '13', 'font_d_size' => '14', 'text_color' => '#000000', 'odd_color' => '#ffffff', 'even_color' => '#eeeeee', 'farsi_numbers' => 'true' ) );
file_put_contents( "$out_dir/widget.html", ob_get_clean() . "\n" );

echo 'Rendered ' . ( count( $cases ) + 1 ) . " snapshots to $out_dir\n";
