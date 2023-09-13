<?php

require_once HBM_PLUGIN_PATH . 'api/class-hbm-encrypt.php'; // include an encryption management class
require_once HBM_PLUGIN_PATH . 'api/class-hbm-callback-handler.php'; // include the callback handler class

/**
 * 
 * 
 * 
 * 
 * 
 */

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
        $framework_choosen = get_option('_hbm-auth-framework');
        switch ($framework_choosen) {
            case 'entra':
                require_once HBM_PLUGIN_PATH . 'api/framework-microsoft-entra/class-entra.php'; // Callback functionality for the Entra authentication
                $this->framework = new HBM_Framework_Entra();
                break;
            case 'cognito':
                require_once HBM_PLUGIN_PATH . 'api/framework-aws-cognito/class-cognito.php'; // Callback functionality for the Cognito authentication
                $this->framework = new HBM_Framework_Cognito();
                break;
            case 'google':
                require_once HBM_PLUGIN_PATH . 'api/framework-google-identity/class-google.php'; // Callback functionality for the Google Identity authentication
                $this->framework = new HBM_Framework_Google();
                break;
            default:
                error_log('No framework choosen');
                break;
        }
        $this->secret_manager = new HBM_Server_JWT_Manager();

        $this->callback_handler = new HBM_Callback_Handler($this->secret_manager);

    }


}