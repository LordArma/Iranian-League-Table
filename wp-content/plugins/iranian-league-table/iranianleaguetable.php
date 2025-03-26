<?php
/*
Plugin Name: Iranian League Table
Plugin URI: https://github.com/LordArma/Iranian-League-Table
Description: Display the Iranian Premier League (Persian Gulf League) or the Iranian League One (Azadegan League) table in Farsi as a widget.
Version: 2.3.3
Author: Arma
Author URI: https://LordArma.com
Text Domain: ilt_domain
Domain Path: /languages
*/

if(!defined('ABSPATH')){
  exit;
}

require_once(plugin_dir_path(__FILE__).'/includes/iranianleaguetable-scripts.php');
require_once(plugin_dir_path(__FILE__).'/includes/iranianleaguetable-class.php');

function register_iranian_league(){
  register_widget('Iranian_League_Widget');
}

add_action('widgets_init', 'register_iranian_league');

function ir_table($atts = [], $content = null, $tag = ''){
  $atts = array_change_key_case( (array) $atts, CASE_LOWER );
  $irt_atts = shortcode_atts(
		array(
			'league' => 'bartar',
      'mode' => 'basic',
      'title_backcolor' => '#212121',
      'title_color' => '#eeeeee',
      'text_color' => '#212121',
      'odd_color' => '#ffffff',
      'even_color' => '#eeeeee',
      'logo_size' => '15',
      'logo' => 'true',
      'title_font' => '13',
      'text_font' => '14',
		), $atts, $tag
	);

  $render = '<div class="vaz3-table" style="direction: rtl; overflow-x: auto;" >';

  $render = $render . table($irt_atts['league'],
                            $irt_atts['mode'],
                            $irt_atts['title_backcolor'],
                            $irt_atts['title_color'],
                            $irt_atts['text_color'],
                            $irt_atts['odd_color'],
                            $irt_atts['even_color'],
                            $irt_atts['logo_size'],
                            $irt_atts['logo'],
                            $irt_atts['title_font'],
                            $irt_atts['text_font'],);
  $renderr = $render . '</div>';
  return $render;
}

add_shortcode('iran_league', 'ir_table');

function ilt_load_textdomain() {
  load_plugin_textdomain( 'ilt_domain', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}
add_action( 'plugins_loaded', 'ilt_load_textdomain' );
