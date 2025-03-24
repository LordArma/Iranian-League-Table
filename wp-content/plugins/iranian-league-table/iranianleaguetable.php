<?php
/*
Plugin Name: Iranian League Table
Plugin URI: https://github.com/LordArma/Iranian-League-Table
Description: Display the Iranian Premier League (Persian Gulf League) or the Iranian League One (Azadegan League) table in Farsi as a widget.
Version: 1.3.1
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
