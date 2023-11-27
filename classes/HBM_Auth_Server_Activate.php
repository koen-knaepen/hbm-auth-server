<?php

namespace HBM\auth_server;

use \HBM\Plugin_management\HBM_Activate;

// Include the necessary WordPress plugin functions
// require_once \HBM_MAIN_UTIL_PATH . 'classes/class-hbm-activate.php';


class HBM_Auth_Server_Activate extends HBM_Activate
{
    function __construct()
    {
        parent::__construct(__NAMESPACE__);
    }

    protected function extend_params(): array
    {
        return [
            'settings' => 'hbm-auth-server',
            'file_pattern' => 'hbm-auth-server-pods-package-',
            'pods_settings_on_activate' => [
                'menu_location' => 'top',
            ],
            'pods_settings_on_deactivate' => [
                'menu_location' => 'submenu',
            ],
        ];
    }
}
