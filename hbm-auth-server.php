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

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    die("You are not supposed to be here");
}

// Initialize the main plugin class
$hbm_server_plugin = HBM_Auth_Server::HBM()::get_instance(__FILE__);
