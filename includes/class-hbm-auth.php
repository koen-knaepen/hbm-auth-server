<?php

require_once HBM_PLUGIN_PATH . 'public/class-hbm-shortcodes.php'; // Shortcodes for the public-facing side of the site
require_once HBM_PLUGIN_PATH . 'admin/class-hbm-auth-admin.php'; // Admin-related functionality
require_once HBM_PLUGIN_PATH . 'api/class-hbm-api.php'; // API-related functionality
require_once HBM_PLUGIN_PATH . 'public/class-hbm-sso-user.php'; // sso user class
require_once HBM_PLUGIN_PATH . 'includes/class-hbm-auth-activate.php'; // Activation-related functionality
require_once HBM_PLUGIN_PATH . 'includes/class-hbm-auth-deactivate.php'; // Deactivation-related functionality

class HBM_Server_Auth
{
    private $admin;
    private $plugin_file;
    private $activate;
    private $deactivate;
    private $sso_user;

    public function __construct($file)
    {
        // Constructor code here
        $current_domain = hbm_get_current_domain();
        if ($current_domain == get_option('_hbm-auth-sso-server-url')) {
            session_start();
            $this->sso_user = new HBM_SSO_User();
        }
        $this->plugin_file = $file;
        $this->admin = new HBM_Server_Auth_Admin();
        $this->activate = new HBM_Auth_Activate();
        $this->deactivate = new HBM_Auth_Deactivate();
    }

    public function run()
    {
    }

    // Other methods related to your plugin's functionality
}
