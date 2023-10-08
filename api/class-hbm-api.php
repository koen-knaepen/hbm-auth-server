<?php

namespace HBM\auth_server;

require_once HBM_PLUGIN_PATH . 'api/class-hbm-encrypt.php'; // include an encryption management class
require_once HBM_PLUGIN_PATH . 'api/class-hbm-callback-handler.php'; // include the callback handler class

class HBM_Server_API
{

    private $secret_manager;
    private $callback_handler;
    private $framework;

    public function __construct()
    {
        $this->set_framework();
    }

    public function set_framework()
    {
        $this->secret_manager = new HBM_Server_JWT_Manager();

        $this->callback_handler = new HBM_Callback_Handler($this->secret_manager);
    }
}
