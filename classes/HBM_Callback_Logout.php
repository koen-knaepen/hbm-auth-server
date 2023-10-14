<?php

namespace HBM\auth_server;

use function HBM\hbm_extract_payload;
use function HBM\hbm_echo_modal;
use function HBM\hbm_set_headers;
use function HBM\hbm_sub_namespace;
use \HBM\HBM_SSO_User_Session;
use \HBM\HBM_Transient_Handler;

/**
 * Summary of class-hbm-callback-api
 * This class is responsible for the callback functionality of the User-PW authentication
 * There cannot be any code that is dependent on the params in the database in this class
 * @package HBM_Auth
 * @subpackage api
 */


class HBM_Callback_Logout
{

    /**
     * Summary of _deprecated_constructor
     * 1. Register the callback endpoint
     */

    public function __construct()
    {
        add_action('rest_api_init', array($this, 'hbm_register_endpoint'));
    }

    /**
     * Summary of hbm_register_callback_endpoint
     * Register the callback endpoints
     * 1. endpoint for login
     * 2. endpoint for logout
     * @return void
     */
    public function hbm_register_endpoint()
    {

        register_rest_route(
            "hbm-" . hbm_sub_namespace(__NAMESPACE__, true) . '/v1',
            '/framework_logout',
            array(
                'methods' => 'GET',
                'callback' => array($this, 'handle_framework_logout'),
                'permission_callback' => '__return_true'
            )
        );
    }

    public function handle_framework_logout(\WP_REST_Request $request)
    {
        $application = $request->get_param('app');
        if (!isset($application)) {
            return new \WP_Error('no_app', 'No application on logout', array('status' => 400));
        }
        $sso_user_session = HBM_SSO_User_Session::get_instance($application);
        $transient = HBM_Transient_Handler::get_instance();
        $state = $transient->get_transient('sso_logout');
        if (!isset($state)) {
            return new \WP_Error('no_state', 'No state received', array('status' => 400));
        }
        $state_payload = hbm_extract_payload($state);
        $mode = $state_payload->mode;
        $logout_url = "{$state_payload->domain}/wp-json/hbm-auth-client/v1/logout-client?state={$state}";
        $sso_user_session->logout_sso_user();
        $transient->delete_transient('sso_logout');
        if ($mode == 'test') {
            $message = "<h3>You are BACK on the SSO Server</h3>"
                . "<p>Logout request received: </p><pre>" . json_encode($state_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "</pre>";
            hbm_echo_modal($logout_url, $message);
        } else {
            hbm_set_headers();
            echo "<script type='text/javascript'>window.location.href = '{$logout_url}';</script>";
        }
    }
}
