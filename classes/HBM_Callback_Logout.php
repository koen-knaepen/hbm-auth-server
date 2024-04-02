<?php

namespace HBM\auth_server;

use HBM\Data_Handlers\Data_Middleware\Data_JWT_Transformation;
use HBM\Instantiations\HBM_Class_Handler;
use HBM\Plugin_Management\HBM_Plugin_Utils;
use HBM\Data_Handlers\HBM_JWT_Helpers;
use HBM\helpers\WP_Rest_Modal;
use HBM\Loader\Browser\Transients;

/**
 * Summary of class-hbm-callback-api
 * This class is responsible for the callback functionality of the User-PW authentication
 * There cannot be any code that is dependent on the params in the database in this class
 * @package HBM_Auth
 * @subpackage api
 */


class HBM_Callback_Logout extends HBM_Class_Handler
{
    use WP_Rest_Modal {
        hbm_set_headers as private;
        hbm_echo_modal as private;
    }

    private $plugin_utils;
    private $user_transient;
    private $sso_user_session;
    /**
     * Summary of _deprecated_constructor
     * 1. Register the callback endpoint
     */

    public function __construct()
    {
        $this->add_to_dna([$this, 'init_pofs']);
    }

    public function init_pofs()
    {
        $this->plugin_utils = HBM_Plugin_Utils::HBM()::get_instance();
        $this->sso_user_session = $this->pof('users');
        $this->user_transient = $this->pof('transient');
        add_action('rest_api_init', array($this, 'hbm_register_endpoint'));
    }


    protected static function set_pattern($options = []): array
    {
        return [
            'pattern' => 'singleton',
            '__ticket' =>
            ['Entry' => ['is_api', ['check_api_namespace', 'hbm-auth-server'], ['check_api_endpoint', 'framework_logout']]],
            '__inject' => [
                'browser:user?users',
                'transientAttribute:user?transient'
            ]
        ];
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
            "hbm-" . $this->plugin_utils->hbm_sub_namespace(__NAMESPACE__, true) . '/v1',
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
        error_log("USER ID: " . print_r($this->sso_user_session->release_key(), true));
        if (!isset($application)) {
            return new \WP_Error('no_app', 'No application on logout', array('status' => 400));
        }
        // $this->sso_user_session->set_application($application);
        $state = $this->user_transient->get('sso_logout', false);
        if (!$state) {
            return new \WP_Error('no_state', 'No state received', array('status' => 400));
        }
        $state_payload = Data_JWT_Transformation::spayload($state);
        $mode = $state_payload['mode'];
        $logout_url = "{$state_payload['domain']}wp-json/hbm-auth-client/v1/logout-client?state={$state}";
        $this->sso_user_session->fdestroyall();
        $this->user_transient->del('sso_logout');
        if ($mode == 'test') {
            $message = "<h3>You are BACK on the SSO Server</h3>"
                . "<p>Logout request received: </p><pre>" . json_encode($state_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "</pre>";
            $this->hbm_echo_modal($logout_url, $message);
        } else {
            $this->hbm_set_headers();
            echo "<script type='text/javascript'>window.location.href = '{$logout_url}';</script>";
        }
    }
}
