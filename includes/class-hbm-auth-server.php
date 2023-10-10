<?php

namespace HBM\auth_server;

use \HBM\HBM_Root;

// Include the necessary HBM and other files
require_once HBM_MAIN_UTIL_PATH . 'classes/class-hbm-root.php'; // Root class template

class HBM_Auth_Server extends HBM_Root
{
    private $admin;
    private $api;
    private $sso_user;

    public function __construct()
    {
        parent::__construct(__NAMESPACE__);
    }

    function init_plugin()
    {
        require_once HBM_PLUGIN_PATH . 'api/class-hbm-callback-handler.php'; // API-related functionality
        require_once HBM_PLUGIN_PATH . 'public/class-hbm-sso-user.php'; // sso user class
        if (is_admin()) {
            require_once HBM_PLUGIN_PATH . 'admin/class-hbm-auth-admin.php'; // Admin-related functionality
            $this->admin = new HBM_Server_Auth_Admin();
        }
        $this->sso_user = new HBM_SSO_User();
        $this->api = new HBM_Callback_Handler();
    }

    function fastlane()
    {
    }
}
