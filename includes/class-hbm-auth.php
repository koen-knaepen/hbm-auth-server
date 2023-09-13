<?php

require_once HBM_PLUGIN_PATH . 'public/class-hbm-shortcodes.php'; // Shortcodes for the public-facing side of the site
require_once HBM_PLUGIN_PATH . 'admin/class-hbm-auth-admin.php'; // Admin-related functionality
require_once HBM_PLUGIN_PATH . 'api/class-hbm-api.php'; // API-related functionality
require_once HBM_PLUGIN_PATH . 'public/class-hbm-sso-user.php'; // sso user class

class HBM_Server_Auth
{
    private $admin;
    private $plugin_file;
    private $api;
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
        $this->api = new HBM_Server_API();
        $this->admin = new HBM_Server_Auth_Admin();

    }

    public function run()
    {
        // Register hooks, filters, and other initialization tasks here
        register_deactivation_hook($this->plugin_file, array($this->admin, 'hbm_plugin_deactivate')); // Deactivation-related functionality
        register_activation_hook($this->plugin_file, array($this->admin, 'hbm_plugin_activate')); // Deactivation-related functionality
        hbm_log('hbm-entra-auth plugin loaded and all hooks registered');
    }

    // Other methods related to your plugin's functionality
}