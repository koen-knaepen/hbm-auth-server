<?php
// uninstall.php

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

include_once HBM_AUTH_SERVER_PATH . 'admin/cleanup-db.php';

$delete_option = get_option('hbm-auth-delete-fields-on-uninstall');

if ($delete_option) {
    hbm_cleanup_database();
}
