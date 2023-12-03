<?php

namespace HBM\auth_server;

use \HBM\Plugin_Management\HBM_Root;
use HBM\Pods_Helpers\Watch_Pod;

// Include the necessary HBM and other files
// require_once HBM_MAIN_UTIL_PATH . 'classes/class-hbm-root.php'; // Root class template
// require_once HBM_MAIN_UTIL_PATH . 'gatekeeper.php'; // Visit class template

class HBM_Auth_Server extends HBM_Root
{
    protected static function set_pattern(): array
    {
        HBM_Server_Auth_Admin::HBM()::get_instance();
        HBM_Callback_Handler::HBM()::get_instance();
        HBM_Callback_Initiate::HBM()::get_instance();
        HBM_Callback_Set_Sso::HBM()::get_instance();
        HBM_Callback_Logout::HBM()::get_instance();
        return array_merge(parent::set_pattern(), []);
    }
}
