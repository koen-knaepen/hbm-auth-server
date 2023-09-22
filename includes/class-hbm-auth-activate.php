<?php

class HBM_Auth_Activate
{

    public function __construct()
    {
        register_activation_hook(HBM_PLUGIN_FILE, array($this, 'hbm_plugin_activate')); // Activation-related functionality

    }

    public function hbm_plugin_activate()
    {
        if (!current_user_can('activate_plugins')) {
            return;
        }

        error_log('Activating hbm-entra-auth plugin...');

        // Path to the main plugin file inside of the wp-content/plugins directory
        $plugin_path = 'pods/init.php';

        // Check if the plugin is active
        if (is_plugin_active($plugin_path)) {
            error_log('Plugin PODS is active');
        } else {
            error_log('Plugin PODS is not active');
        }
    }
}
