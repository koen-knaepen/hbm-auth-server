<?php
/*
 * Plugin Name:       HBM Auth Server
 * Plugin URI:        https://hackbizmodels.academy
 * Description:       HBM Auth Server transforms your Wordpress Install into a Single Sign-On Server with the help of AWS Cognito or MS Entra.<br> The free PODS plugin is required and will be installed or activated on activation of this plugin. Make sure you are connected to the internet.
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

namespace HBM\auth_server;

use function \HBM\hbm_init_constants;


// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    die("You are not supposed to be here");
}

// HBM_MAIN_UTIL_PATH is a global constant defined to get access to the main utilities.
if (!defined('HBM_MAIN_UTIL_PATH')) define('HBM_MAIN_UTIL_PATH', \WP_PLUGIN_DIR . '/hbm-main/main-utility/');

// CREATE THE HBM CONSTANTS 
// The hbm-constants.php file is located in the main-utility folder.    
// It contains the hbm_define_constants() function that defines the constants for the plugin paths.
// HBM_PLUGIN_PATH, HBM_PLUGIN_URL
// HBM_PLUGIN_FILE, HBM_PLUGIN_BASENAME
// HBM_PLUGIN_NAME, HBM_PLUGIN_VERSION
require_once \HBM_MAIN_UTIL_PATH .  'main-utils.php'; // Constants class

hbm_init_constants(__NAMESPACE__, __FILE__);

// Include necessary files
require_once HBM_PLUGIN_PATH . 'admin/log-script-server.php'; // Logging functionality
require_once HBM_PLUGIN_PATH . 'includes/class-hbm-auth-server.php'; // Main plugin class

// Bootstrap Carbon Fields
add_action('after_setup_theme', __NAMESPACE__ . '\crb_load');
function crb_load()
{
    if (!class_exists('\Carbon_Fields\Carbon_Fields')) {
        require_once HBM_PLUGIN_PATH . 'vendor/autoload.php';
        \Carbon_Fields\Carbon_Fields::boot();
    }
}

// Initialize the main plugin class
$hbm_server_plugin = new HBM_Auth_Server();
