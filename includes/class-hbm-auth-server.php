<?php

namespace HBM\auth_server;

use function \HBM\hbm_main_active;

// require_once HBM_PLUGIN_PATH . 'public/class-hbm-shortcodes.php'; // Shortcodes for the public-facing side of the site
// require_once HBM_PLUGIN_PATH . 'admin/class-hbm-auth-admin.php'; // Admin-related functionality
// require_once HBM_PLUGIN_PATH . 'api/class-hbm-api.php'; // API-related functionality
// require_once HBM_PLUGIN_PATH . 'public/class-hbm-sso-user.php'; // sso user class
require_once HBM_PLUGIN_PATH . 'includes/class-hbm-auth-activate.php'; // Activation-related functionality
require_once \HBM_MAIN_UTIL_PATH . 'plugin-utils.php'; // Plugin utilities
// require_once HBM_PLUGIN_PATH . 'includes/class-hbm-auth-deactivate.php'; // Deactivation-related functionality

class HBM_Server_Auth
{
    private $admin;
    private $main_activation;
    private $api;
    private $activate;
    private $deactivate;
    private $sso_user;

    public function __construct()
    {
        add_action('hbm_main_loaded', array($this, 'init_plugin'), 10, 1);
        if (is_admin()) {
            $this->activate = new HBM_Auth_Activate();
        }
    }



    function init_plugin()
    {
        if (!hbm_main_active()) {
            return;
        }
        require_once HBM_PLUGIN_PATH . 'api/class-hbm-api.php'; // API-related functionality
        $current_domain = \hbm\hbm_get_current_domain();
        if ($current_domain == get_option('_hbm-auth-sso-server-url')) {
            session_start();
            require_once HBM_PLUGIN_PATH . 'public/class-hbm-sso-user.php'; // sso user class
            $this->sso_user = new HBM_SSO_User();
        }
        if (is_admin()) {
            require_once HBM_PLUGIN_PATH . 'admin/class-hbm-auth-admin.php'; // Admin-related functionality
            require_once HBM_PLUGIN_PATH . 'includes/class-hbm-auth-deactivate.php'; // Deactivation-related functionality
            $this->admin = new HBM_Server_Auth_Admin();
            $this->deactivate = new HBM_Auth_Deactivate();
        }
        $this->api = new HBM_Server_API();
    }
}
