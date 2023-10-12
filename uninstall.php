<?php

namespace HBM\auth_server;

namespace HBM\auth_server;
// uninstall.php

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// include_once HBM_PLUGIN_PATH . 'admin/cleanup-db.php';

$delete_option = get_option('hbm-auth-delete-fields-on-uninstall');

if ($delete_option) {
    hbm_cleanup_database();
}
