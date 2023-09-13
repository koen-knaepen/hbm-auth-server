<?php
// deactivate.php
include_once HBM_PLUGIN_PATH . 'admin/cleanup-db.php';

function hbm_plugin_deactivate()
{
    if (!current_user_can('activate_plugins')) {
        return;
    }

    $delete_option = carbon_get_theme_option('hbm-auth-delete-fields-on-deactivate');

    hbm_log('Deactivating hbm-entra-auth plugin...' . $delete_option);

    if ($delete_option) {
        hbm_log('cleaning up the database for hbm-entra-auth plugin');
        hbm_cleanup_database();
    }
}