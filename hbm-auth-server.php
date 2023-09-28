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

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    die("You are not supposed to be here");
}

// Disable SSL verification for the API requests !!!! REMOVE THIS IN PRODUCTION !!!!
// add_filter('https_ssl_verify', '__return_false');
// add_filter('https_local_ssl_verify', '__return_false');

// Define constants for plugin paths
define('HBM_AUTH_SERVER_PATH', plugin_dir_path(__FILE__));
define('HBM_AUTH_SERVER_URL', plugin_dir_url(__FILE__));
define('HBM_AUTH_SERVER_FILE', __FILE__);
define('HBM_AUTH_SERVER_BASENAME', plugin_basename(__FILE__));



// Include necessary files
require_once HBM_AUTH_SERVER_PATH . 'admin/log-script-server.php'; // Logging functionality
require_once HBM_AUTH_SERVER_PATH . 'includes/class-hbm-auth.php'; // Main plugin class
require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

// Bootstrap Carbon Fields
add_action('after_setup_theme', 'crb_load');
function crb_load()
{
    require_once HBM_AUTH_SERVER_PATH . 'vendor/autoload.php';
    \Carbon_Fields\Carbon_Fields::boot();
}

// Initialize the main plugin class
$hbm_server_plugin = new HBM_Server_Auth(HBM_AUTH_SERVER_FILE);

function test_all_hooks_of_plugin()
{
    $pods_api = pods_api('hbm-auth-server-application');

    $framework_object = $pods_api->load_field('app_framework');
    $framework_id = $framework_object->get_arg('id');
    $framework_to_save = array(
        'id' => $framework_id,
        'pod' => 'hbm-auth-server-application',
        'name' => 'app_framework',
        'pick_custom' => 'cognito|AWS Cognito' . PHP_EOL . 'entra|Microsoft Entra',
    );
    $pods_api->save_field($framework_to_save);
    // pods('hbm-auth-server-application')->save($framework_options);
    // $setting_methods = get_class_methods($settings);
    // error_log('settings methods' . print_r($setting_methods, true));
    // $settings_data = $settings->field('hbm_auth_server_test_domain');
    // error_log('settings data' . print_r($settings_data, true));
}

// add_action('admin_init', 'test_all_hooks_of_plugin');
// $hbm_server_plugin->run();
