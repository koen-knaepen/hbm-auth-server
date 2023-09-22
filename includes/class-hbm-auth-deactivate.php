<?php

class HBM_Auth_Deactivate
{

    public function __construct()
    {
        register_deactivation_hook(HBM_PLUGIN_FILE, array($this, 'hbm_plugin_deactivate')); // Activation-related functionality

    }

    public function hbm_plugin_deactivate()
    {
        if (!current_user_can('activate_plugins')) {
            return;
        }

        error_log('Deactivating hbm-entra-auth plugin (new)...');

        $delete_option = carbon_get_theme_option('hbm-auth-delete-fields-on-deactivate');

        if ($delete_option) {
            error_log("Deactivating hbm-entra-auth plugin..." . PHP_EOL . "Delete fields on deactivation: " . $delete_option);
            hbm_cleanup_database();
        }
    }
}
