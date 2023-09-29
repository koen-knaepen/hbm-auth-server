<?php

namespace HBM\auth_server;

use \HBM\HBM_Activate;

// Include the necessary WordPress plugin functions
require_once \HBM_MAIN_UTIL_PATH . 'classes/class-hbm-activate.php';


class HBM_Auth_Activate extends HBM_Activate
{
    function __construct()
    {
        $this->pods_params['settings'] = 'hbm-auth-server';
        $this->pods_params['file_pattern'] = 'hbm-auth-server-pods-package';
        parent::__construct(__NAMESPACE__);
    }
}
