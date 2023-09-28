<?php

// require_once HBM_AUTH_SERVER_PATH . 'public/class-hbm-shortcodes.php'; // Shortcodes for the public-facing side of the site
// require_once HBM_AUTH_SERVER_PATH . 'admin/class-hbm-auth-admin.php'; // Admin-related functionality
// require_once HBM_AUTH_SERVER_PATH . 'api/class-hbm-api.php'; // API-related functionality
// require_once HBM_AUTH_SERVER_PATH . 'public/class-hbm-sso-user.php'; // sso user class
require_once HBM_AUTH_SERVER_PATH . 'includes/class-hbm-auth-activate.php'; // Activation-related functionality
// require_once HBM_AUTH_SERVER_PATH . 'includes/class-hbm-auth-deactivate.php'; // Deactivation-related functionality

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
        $this->main_activation = $this->hbm_check_activated_plugins();
        if ($this->main_activation) {
            if (is_admin()) {
                require_once HBM_AUTH_SERVER_PATH . 'admin/class-hbm-auth-admin.php'; // Admin-related functionality
                require_once HBM_AUTH_SERVER_PATH . 'includes/class-hbm-auth-deactivate.php'; // Deactivation-related functionality
            } else {
                // require_once HBM_AUTH_SERVER_PATH . 'public/class-hbm-shortcodes.php'; // Shortcodes for the public-facing side of the site
                require_once HBM_AUTH_SERVER_PATH . 'public/class-hbm-sso-user.php'; // sso user class
            }
            require_once HBM_AUTH_SERVER_PATH . 'api/class-hbm-api.php'; // API-related functionality
            add_action('hbm_main_loaded', array($this, 'wait_for_hbm_plugins'), 10, 1);
        }
        if (is_admin()) {
            $this->activate = new HBM_Auth_Activate($this->main_activation);
        }
    }



    function wait_for_hbm_plugins()
    {
        $current_domain = hbm_get_current_domain();
        if ($current_domain == get_option('_hbm-auth-sso-server-url')) {
            session_start();
            $this->sso_user = new HBM_SSO_User();
        }
        if (is_admin()) {
            $this->admin = new HBM_Server_Auth_Admin();
            $this->deactivate = new HBM_Auth_Deactivate();
        }
        $this->api = new HBM_Server_API();
    }

    function hbm_check_activated_plugins()
    {
        $result = false;
        $plugin_path = 'hbm-main/hbm-main.php';
        $all_plugins = get_plugins();

        if (isset($all_plugins[$plugin_path])) {
            if (is_plugin_active($plugin_path)) {
                $result = true;
            }
        }
        return $result;
    }
}
