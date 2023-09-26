<?php

require_once HBM_PLUGIN_PATH . 'admin/options/class-options.php'; // Admin-related functionality
require_once HBM_PLUGIN_PATH . 'admin/class-hbm-admin-shortcodes.php'; // Admin-related functionality


/**
 * Class HBM_Auth_Admin
 * This class is responsible for the admin panel functionality
 * @package HBM_Entra_Auth
 * @subpackage admin
 */

class HBM_Server_Auth_Admin
{
    private $admin_options;
    private $admin_shortcodes;

    /**
     * Summary of __construct
     * Start the admin panel functionality
     * Create a new instance of the class
     * @return void
     */
    public function __construct()
    {
        if (is_admin()) {
            $this->admin_options = new HBM_Server_Auth_Admin_Options();
            $this->admin_shortcodes = new HBM_Server_Auth_Admin_Shortcodes();
            settings_errors('hbm-auth', false, true); // Display the error message in the admin panel
            add_filter('all_plugins', array($this, 'hbm_modify_plugin_data'), 10, 1);
        }
    }

    function hbm_modify_plugin_data($all_plugins)
    {
        $original_plugin_path = 'hbm-auth-server/hbm-auth-server.php';
        $is_active = is_plugin_active($original_plugin_path);
        if ($is_active) {
            $plugin_path = 'pods/init.php';
            if (isset($all_plugins[$plugin_path])) {
                $all_plugins[$plugin_path]['Description'] .= ' Do not deactivate or delete this plugin as long as you want to use the HBM Auth Server plugin.';
            }
            $all_plugins[$original_plugin_path]['Description'] = 'HBM Auth Server transforms your Wordpress Install into a Single Sign-On Server with the help of AWS Cognito or MS Entra.'
                . ' Make sure you NEVER deactivate or delete the PODS plugin as long as you want to use the HBM Auth Server plugin.';
            return $all_plugins;
        }
    }
}
