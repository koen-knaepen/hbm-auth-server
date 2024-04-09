<?php

namespace HBM\auth_server;

use HBM\Instantiations\HBM_Class_Handler;
use HBM\Plugin_Management\HBM_Admin_Shortcodes;

class HBM_Server_Auth_Admin extends HBM_Class_Handler
{

    /**
     * Summary of __construct
     * Start the admin panel functionality
     * Create a new instance of the class
     * @return void
     */
    public function __construct()
    {
        HBM_Admin_Shortcodes::HBM()::get_instance();
        HBM_Server_Auth_Admin_Shortcodes::HBM()::get_instance();
        add_filter('all_plugins', array($this, 'hbm_modify_plugin_data'), 10, 1);
    }

    protected static function set_pattern($options = []): array
    {
        return [
            'pattern' => 'singleton',
            '__ticket' =>
            [
                'Entry' => [
                    '_OR_' => [
                        ['admin_page', 'plugins'],
                        '_AND_' => [
                            'is_admin',
                            [
                                'uri_params',
                                [
                                    'page',
                                    [
                                        'pods-settings-hbm-auth-server',
                                        'pods-manage-hbm-auth-server-application'
                                    ]
                                ]
                            ],
                        ],
                    ]
                ]
            ],
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
}
