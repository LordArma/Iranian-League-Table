<?php
/**
 * Plugin Name: Iranian League Table
 * Plugin URI: https://github.com/LordArma/Iranian-League-Table
 * Description: Display the Iranian Persian Gulf Pro League, League One (Azadegan) or Women's League (Kowsar) standings in Farsi, as a widget or with the [iran_league] shortcode, designed visually in the Shortcode Builder.
 * Version: 4.4.1
 * Author: Arma
 * Author URI: https://LordArma.com
 * Text Domain: iranian-league-table
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Update URI: https://github.com/LordArma/Iranian-League-Table
 *
 * @package Iranian_League_Table
 */

defined( 'ABSPATH' ) || exit;

define( 'ILT_VERSION', '4.4.1' );
define( 'ILT_PLUGIN_FILE', __FILE__ );
define( 'ILT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

require_once ILT_PLUGIN_DIR . 'includes/class-ilt-leagues.php';
require_once ILT_PLUGIN_DIR . 'includes/class-ilt-attributes.php';
require_once ILT_PLUGIN_DIR . 'includes/class-ilt-api.php';
require_once ILT_PLUGIN_DIR . 'includes/class-ilt-logos.php';
require_once ILT_PLUGIN_DIR . 'includes/class-ilt-jalali.php';
require_once ILT_PLUGIN_DIR . 'includes/class-ilt-renderer.php';
require_once ILT_PLUGIN_DIR . 'includes/class-ilt-shortcode.php';
require_once ILT_PLUGIN_DIR . 'includes/class-ilt-widget.php';
require_once ILT_PLUGIN_DIR . 'includes/class-ilt-presets.php';
require_once ILT_PLUGIN_DIR . 'includes/class-ilt-tables.php';
require_once ILT_PLUGIN_DIR . 'includes/class-ilt-rest.php';
require_once ILT_PLUGIN_DIR . 'includes/class-ilt-block.php';
require_once ILT_PLUGIN_DIR . 'includes/class-ilt-updater.php';
require_once ILT_PLUGIN_DIR . 'includes/class-ilt-plugin.php';

ILT_Plugin::init();
ILT_Tables::init();
ILT_Rest::init();
ILT_Block::init();
ILT_Updater::init();

if ( is_admin() ) {
	require_once ILT_PLUGIN_DIR . 'includes/admin/class-ilt-admin.php';
	require_once ILT_PLUGIN_DIR . 'includes/admin/class-ilt-builder-page.php';
	require_once ILT_PLUGIN_DIR . 'includes/admin/class-ilt-tables-list.php';
	require_once ILT_PLUGIN_DIR . 'includes/admin/class-ilt-settings-page.php';
	ILT_Admin::init();
}
