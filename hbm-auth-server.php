<?php
/*
 * Plugin Name:       HBM Auth Server
 * Plugin URI:        https://hackbizmodels.academy
 * Description:       Handle the basics with this plugin.
 * Version:           1.10.3
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Koen Knaepen
 * Author URI:        https://linkedin.com/koenknaepen
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Update URI:        https://hackbizmodels.academy/updates/hbm-auth
 * Text Domain:       hbm-auth-server
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    die("You are not supposed to be here");
}

// Disable SSL verification for the API requests !!!! REMOVE THIS IN PRODUCTION !!!!
// add_filter('https_ssl_verify', '__return_false');
// add_filter('https_local_ssl_verify', '__return_false');

// Define constants for plugin paths
define('HBM_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('HBM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('HBM_PLUGIN_FILE', __FILE__);



// Include necessary files
require_once HBM_PLUGIN_PATH . 'admin/log-script.php'; // Logging functionality
require_once HBM_PLUGIN_PATH . 'includes/class-hbm-auth.php'; // Main plugin class
require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

// Bootstrap Carbon Fields
add_action('after_setup_theme', 'crb_load');
function crb_load()
{
    require_once HBM_PLUGIN_PATH . 'vendor/autoload.php';
    \Carbon_Fields\Carbon_Fields::boot();
}

// Initialize the main plugin class
$hbm_server_plugin = new HBM_Server_Auth(HBM_PLUGIN_FILE);
// $hbm_server_plugin->run();