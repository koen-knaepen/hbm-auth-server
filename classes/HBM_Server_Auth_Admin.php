<?php

namespace HBM\auth_server;

use HBM\Instantiations\HBM_Class_Handler;
use function HBM\hbm_create_uuid;

// require_once HBM_PLUGIN_PATH . 'admin/class-hbm-admin-shortcodes.php'; // Admin-related functionality
// require_once HBM_MAIN_UTIL_PATH . 'helpers.php'; // Helper functions
// // require_once HBM_MAIN_UTIL_PATH . 'pods-act.php';


/**
 * Class HBM_Auth_Admin
 * This class is responsible for the admin panel functionality
 * @package HBM_Entra_Auth
 * @subpackage admin
 */

class HBM_Server_Auth_Admin extends HBM_Class_Handler
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
        $this->admin_shortcodes = HBM_Server_Auth_Admin_Shortcodes::HBM()::get_instance();
        add_filter('all_plugins', array($this, 'hbm_modify_plugin_data'), 10, 1);
        $this->on_save_settings_pods();
    }

    protected static function set_pattern(): array
    {
        return [
            'pattern' => 'singleton',
            '__ticket' =>
            ['Entry' => ['is_admin', ['uri_params', ['page', ['pods-settings-hbm-auth-server']]]]],
        ];
    }


    function hbm_modify_plugin_data($all_plugins)
    {
        $is_active = is_plugin_active(HBM_PLUGIN_BASENAME);
        if ($is_active) {
            $all_plugins[HBM_PLUGIN_BASENAME]['Description'] = 'HBM Auth Server transforms your Wordpress Install into a Single Sign-On Server with the help of AWS Cognito or MS Entra.'
                . ' Make sure you NEVER deactivate or delete the HBM Main Plugin or PODS plugin as long as you want to use the HBM Auth Server plugin.';
            return $all_plugins;
        }
    }

    function on_save_settings_pods()
    {
        // Insert here all the actions that need to be done when the settings are saved
        add_action('pods_api_post_save_pod_item_hbm-auth-server-application', array($this, 'hbm_on_save_an_application'), 10, 3);
    }

    function hbm_on_save_an_application($pieces, $is_new_item, $id)
    {
        if ($is_new_item) {
            $uuid = hbm_create_uuid();
            \hbm_change_pods_item_field('hbm-auth-server-application', $id, array(
                'application_uid' => $uuid
            ));
        }
    }
}
