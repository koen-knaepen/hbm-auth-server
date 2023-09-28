<?php
// deactivate.php
include_once HBM_AUTH_SERVER_PATH . 'admin/cleanup-db.php';

function hbm_plugin_deactivate()
{
    if (!current_user_can('activate_plugins')) {
        return;
    }

    $delete_option = carbon_get_theme_option('hbm-auth-delete-fields-on-deactivate');

    error_log('Deactivating hbm-entra-auth plugin... and cleaning the database' . $delete_option);

    if ($delete_option) {
        hbm_log('cleaning up the database for hbm-entra-auth plugin');
        hbm_cleanup_database();
    }
}
