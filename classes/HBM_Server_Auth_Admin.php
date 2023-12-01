<?php

namespace HBM\auth_server;

use HBM\Instantiations\HBM_Class_Handler;
use HBM\Loader\Alien_Hooks;
use function HBM\hbm_create_uuid;

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
        // Alien_Hooks::HBM()::get_instance()->register_proxy(HBM_MAIN_PROXIES . 'Pods_Proxy.php', 'Pods');
        $this->admin_shortcodes = HBM_Server_Auth_Admin_Shortcodes::HBM()::get_instance();
        add_filter('all_plugins', array($this, 'hbm_modify_plugin_data'), 10, 1);
        $this->on_save_settings_pods();
    }

    protected static function set_pattern(): array
    {
        return [
            'pattern' => 'singleton',
            '__ticket' =>
            ['Entry' => ['is_admin', ['uri_params', ['page', ['pods-settings-hbm-auth-server', 'pods-manage-hbm-auth-server-application']]]]],
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
        error_log("on_save_settings_pods is activated");
        add_action('pods_api_pre_save_pod_item_hbm-auth-server-application', array($this, 'hbm_on_save_an_application'), 10, 3);
    }

    function hbm_on_save_an_application($pieces, $is_new_item, $id)
    {
        error_log("hbm_on_save_an_application is activated :new item? " . print_r($is_new_item, true) . " id " . print_r($id, true));
    }
}
