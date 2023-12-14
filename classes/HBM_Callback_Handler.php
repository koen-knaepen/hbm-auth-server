<?php

namespace HBM\auth_server;

use HBM\Instantiations\HBM_Class_Handler;
use \HBM\Cookies_And_Sessions\HBM_State_Manager;
use HBM\helpers\HBM_Main_Utils;
use \HBM\Plugin_Management\HBM_Plugin_Utils;
use HBM\Data_Handlers\HBM_Data_Helpers;
use HBM\Database_Sessions\Pods_Session_Factory;
use \HBM\Cookies_And_Sessions\HBM_Session;
use HBM\helpers\WP_Rest_Modal;

/**
 * Summary of class-hbm-callback-api
 * This class is responsible for the callback functionality of the User-PW authentication
 * There cannot be any code that is dependent on the params in the database in this class
 * @package HBM_Auth
 * @subpackage api
 */


class HBM_Callback_Handler extends HBM_Class_Handler
{
    use HBM_Session {
        user_session as private;
    }

    use HBM_Data_Helpers {
        hbm_extract_payload as private;
    }

    use HBM_Main_Utils {
        hbm_get_current_domain as private;
    }
    use WP_Rest_Modal {
        hbm_set_headers as private;
        hbm_echo_modal as private;
    }

    private $sso_user_session = null;
    private $plugin_utils;
    private object $state_manager;
    private object $pods_session;
    /**
     * Summary of _deprecated_constructor
     * 1. Register the callback endpoint
     */

    public function __construct()
    {
        $this->sso_user_session = $this->user_session();
        $this->plugin_utils = HBM_Plugin_Utils::HBM()::get_instance();
        $this->state_manager = HBM_State_Manager::HBM()::get_instance();
        $this->pods_session = Pods_Session_Factory::HBM()::get_instance();
        add_action('rest_api_init', array($this, 'hbm_register_endpoint'));
    }

    protected static function set_pattern(): array
    {
        return [
            'pattern' => 'singleton',
            '__ticket' => ['Entry' => ['is_api',  ['check_api_namespace', 'hbm-auth-server'], ['check_api_endpoint', 'callback']]],
        ];
    }

    private function init_auth_framework($application)
    {
        $framework = $application['app_framework'];
        return HBM_Auth_Framework::get_instance($framework);
    }

    private function get_application($input_domain)
    {
        $domain = $input_domain;
        $application = $this->pods_session->HBM_pod('hbm-auth-server-site', 'application', ['name' => $domain])->get_raw_data();
        if ($application) {
            $this->sso_user_session->set_application($application['id']);
            return $application;
        } else {
            throw new \Exception("No application found for domain {$domain}, please see the administrator");
        }
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
            '/callback',
            array(
                'methods' => 'GET',
                'callback' => array($this, 'handle_callback'),
                'permission_callback' => '__return_true'
            )
        );
    }


    /**
     * Summary of hbm_handle_callback
     * @param WP_REST_Request $request The request object
     * @return WP_Error|WP_REST_Response The response object
     * 
     */
    public function handle_callback(\WP_REST_Request $request)
    {
        // Get the authorization code from Framework
        $state_urlcoded = $request->get_param('state');
        $state = urldecode($state_urlcoded);
        $state_payload = $this->hbm_extract_payload($state);
        $code = $request->get_param('code');

        if (empty($code)) {
            return new \WP_Error('no_code', 'No code received', array('status' => 400));
        }
        $application = $this->get_application($state_payload['domain']);
        if (!$application) {
            new \WP_Error('no_site', 'No site found', array('status' => 400));
        }
        $framework_api = $this->init_auth_framework($application);
        // Exchange the authorization code for tokens
        $tokens = $framework_api->exchange_code_for_tokens($code, $application);
        $framework_context = $framework_api->get_framework_context();

        if (isset($tokens['error'])) {
            return new \WP_Error('token_error', $tokens['error'], array('status' => 400));
        }

        $id_token = $tokens['id_token'];
        $verified_user = $this->hbm_extract_payload($id_token);
        $framework_user = $framework_api->transform_to_wp_user($verified_user, $state_payload);
        //create a JWT token that can be verified on return
        $site_domain = $this->hbm_get_current_domain();
        $verify_payload = array(
            'role' => 'subscriber',
            'time' => time(),
            'auth_domain' => $site_domain,
            'origin_domain' => $state_payload['domain'],
            'action' => $state_payload['action'],
            'mode' => $state_payload['mode'],
        ) + (array) $framework_user;
        $this->sso_user_session->set_sso_user($verify_payload);

        $verify_token = json_decode($this->state_manager->encode_transient_jwt($verify_payload,  'hbm-auth-access-', false));
        $verify_token_urlcoded = urlencode($verify_token->jwt);
        $redirect_url = "{$state_payload['domain']}/wp-json/hbm-auth-client/v1/validate_token?state={$state_urlcoded}&access_code={$verify_token_urlcoded}";

        if ($state_payload['mode'] == 'test') {

            $message = "<h3>You are on the SSO Server</h3>"
                . "<p>Authentication from {$framework_context->label} received: </p><pre>" . json_encode($state_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "</pre>"
                . "<p>{$framework_context->label} user: </p><pre>" . json_encode($verified_user, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . '</pre>'
                . "<p>WP user: </p><pre>" . json_encode($framework_user, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . '</pre>';
            $this->hbm_echo_modal($redirect_url, $message);
        } else {
            $this->hbm_set_headers();
            echo "<script type='text/javascript'>    window.location.href = '{$redirect_url}}';</script>";
        }
    }
}
