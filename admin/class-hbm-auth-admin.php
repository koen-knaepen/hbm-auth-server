<?php

namespace HBM\auth_server;

require_once HBM_PLUGIN_PATH . 'admin/class-hbm-admin-shortcodes.php'; // Admin-related functionality


/**
 * Class HBM_Auth_Admin
 * This class is responsible for the admin panel functionality
 * @package HBM_Entra_Auth
 * @subpackage admin
 */

class HBM_Server_Auth_Admin
{
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
            $this->admin_shortcodes = new HBM_Server_Auth_Admin_Shortcodes();
            add_filter('all_plugins', array($this, 'hbm_modify_plugin_data'), 10, 1);
        }
    }

    function hbm_modify_plugin_data($all_plugins)
    {
        $original_plugin_path = HBM_PLUGIN_BASENAME;
        $is_active = is_plugin_active(HBM_PLUGIN_BASENAME);
        if ($is_active) {
            $all_plugins[HBM_PLUGIN_BASENAME]['Description'] = 'HBM Auth Server transforms your Wordpress Install into a Single Sign-On Server with the help of AWS Cognito or MS Entra.'
                . ' Make sure you NEVER deactivate or delete the HBM Main Plugin or PODS plugin as long as you want to use the HBM Auth Server plugin.';
            return $all_plugins;
        }
    }
}
