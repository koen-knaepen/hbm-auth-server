<?php

namespace HBM\auth_server;

use \HBM\Plugin_Management\HBM_Root;

// Include the necessary HBM and other files
// require_once HBM_MAIN_UTIL_PATH . 'classes/class-hbm-root.php'; // Root class template
// require_once HBM_MAIN_UTIL_PATH . 'gatekeeper.php'; // Visit class template

class HBM_Auth_Server extends HBM_Root
{
    private $admin;
    private $callback_handler;
    private $callback_initiate;
    private $callback_set_sso;
    private $callback_logout;

    public function __construct($file)
    {
        parent::__construct(__NAMESPACE__, $file);
    }

    protected static function set_pattern(): array
    {
        $pattern = parent::set_pattern();
        return array_merge_recursive(
            $pattern,
            [
                '__ticket' =>
                ['Entry' => [
                    '_OR_' =>
                    [
                        ['check_api_namespace', 'hbm-auth-server'],
                        '_AND_' => [
                            'is_admin',
                            [
                                'uri_params',
                                ['page', [
                                    'pods-settings-hbm-auth-server',
                                    'pods-manage-hbm-auth-server-application',
                                    'pods-manage-hbm-auth-server-site'
                                ]]
                            ]
                        ],
                    ]
                ]],
            ]
        );
    }
    function init_plugin()
    {

        // require_once HBM_PLUGIN_PATH . 'admin/class-hbm-auth-admin.php'; // Admin-related functionality
        $this->admin = HBM_Server_Auth_Admin::HBM()::get_instance();

        $this->callback_handler = HBM_Callback_Handler::HBM()::get_instance();
        $this->callback_initiate = HBM_Callback_Initiate::HBM()::get_instance();
        $this->callback_set_sso = HBM_Callback_Set_Sso::HBM()::get_instance();
        $this->callback_logout = HBM_Callback_Logout::HBM()::get_instance();
    }

    function fastlane()
    {
    }
}
