<?php

namespace HBM\auth_server;

use \HBM\HBM_Root;
use function HBM\hbm_get_visit;
use function HBM\hbm_check_tickets;

// Include the necessary HBM and other files
require_once HBM_MAIN_UTIL_PATH . 'classes/class-hbm-root.php'; // Root class template
require_once HBM_MAIN_UTIL_PATH . 'gatekeeper.php'; // Visit class template

class HBM_Auth_Server extends HBM_Root
{
    private $admin;
    private $api;
    private $sso_user;

    public function __construct()
    {
        parent::__construct(__NAMESPACE__);
    }

    function init_plugin()
    {

        if (is_admin()) {
            require_once HBM_PLUGIN_PATH . 'admin/class-hbm-auth-admin.php'; // Admin-related functionality
            $this->admin = new HBM_Server_Auth_Admin();
        }

        error_log('Visit from class HBM_Auth_Server' . print_r(hbm_get_visit(), true));
        $ticket = hbm_check_tickets(
            'hbm_auth_server::api',
            array(
                'Entry' => [
                    array(
                        "is_api" => ["true"],
                        "api_namespace" => ["hbm-auth-server"],

                    ),
                    true,
                    true
                ]
            )
        );
        error_log('Ticket from class HBM_Auth_Server' . print_r($ticket, true));
        if ($ticket) {
            require_once HBM_PLUGIN_PATH . 'api/class-hbm-callback-handler.php'; // API-related functionality
            $this->api = new HBM_Callback_Handler();
        }
    }

    function fastlane()
    {
    }
}
