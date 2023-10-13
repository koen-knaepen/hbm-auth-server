<?php

namespace HBM\auth_server;

use function HBM\hbm_extract_payload;
use function HBM\hbm_echo_modal;
use function HBM\hbm_set_headers;
use function HBM\hbm_extract_domain;
use function HBM\hbm_sub_namespace;
use function HBM\hbm_get_current_domain;
use function HBM\hbm_encode_transient_jwt;
use function HBM\hbm_decode_transient_jwt;
use \HBM\HBM_SSO_User_Session;

/**
 * Summary of class-hbm-callback-api
 * This class is responsible for the callback functionality of the User-PW authentication
 * There cannot be any code that is dependent on the params in the database in this class
 * @package HBM_Auth
 * @subpackage api
 */


class HBM_Callback_Handler
{

    /**
     * Summary of _deprecated_constructor
     * 1. Register the callback endpoint
     */
    public function __construct()
    {
        add_action('rest_api_init', array($this, 'hbm_register_callback_endpoint'));
        add_filter('hbm_get_redirect_url', array($this, 'get_redirect_url'), 10, 2);
    }


    private function init_auth_framework($application)
    {
        $framework = $application['app_framework'];
        return HBM_Auth_Framework::get_instance($framework);
    }

    private function get_application($input_domain)
    {
        $domain = hbm_extract_domain($input_domain);
        $sites = \HBM\hbm_fetch_pods_act('hbm-auth-server-site', array('name' => $domain));
        if (empty($sites)) {
            return false;
        }
        return $sites[0]['application'];
    }
    public function enqueue_auth_script()
    {
    }


    /**
     * Summary of hbm_register_callback_endpoint
     * Register the callback endpoints
     * 1. endpoint for login
     * 2. endpoint for logout
     * @return void
     */
    public function hbm_register_callback_endpoint()
    {
        register_rest_route(
            "hbm-" . hbm_sub_namespace(__NAMESPACE__, true) . '/v1',
            '/callback',
            array(
                'methods' => 'GET',
                'callback' => array($this, 'handle_callback'),
                'permission_callback' => '__return_true'
            )
        );

        register_rest_route(
            "hbm-" . hbm_sub_namespace(__NAMESPACE__, true) . '/v1',
            '/initiate',
            array(
                'methods' => 'GET',
                'callback' => array($this, 'initiate_callback'),
                'permission_callback' => '__return_true'
            )
        );

        register_rest_route(
            "hbm-" . hbm_sub_namespace(__NAMESPACE__, true) . '/v1',
            '/framework_logout',
            array(
                'methods' => 'GET',
                'callback' => array($this, 'handle_framework_logout'),
                'permission_callback' => '__return_true'
            )
        );

        register_rest_route(
            "hbm-" . hbm_sub_namespace(__NAMESPACE__, true) . '/v1',
            '/sso_status',
            array(
                'methods' => 'GET',
                'callback' => array($this, 'callback_set_sso'),
                'permission_callback' => '__return_true'
            )
        );
    }

    function log_request($request)
    {
        $all_params = $request->get_params();
        $all_headers = $request->get_headers();
        $body = $request->get_body();
        error_log('Request received in callback endpoint: ');
        error_log('All params: ' . print_r($all_params, true));
        error_log('All headers: ' . print_r($all_headers, true));
        error_log('Body: ' . $body);
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
        // $this->log_request($request);
        $state_urlcoded = $request->get_param('state');
        $state = urldecode($state_urlcoded);
        $state_payload = hbm_extract_payload($state);
        $code = $request->get_param('code');

        if (empty($code)) {
            return new \WP_Error('no_code', 'No code received', array('status' => 400));
        }
        $application = $this->get_application($state_payload->domain);
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
        $verified_user = hbm_extract_payload($id_token);
        $framework_user = $framework_api->transform_to_wp_user($verified_user, $state_payload);
        //create a JWT token that can be verified on return
        $site_domain = hbm_get_current_domain();
        $verify_payload = array(
            'role' => 'subscriber',
            'time' => time(),
            'auth_domain' => $site_domain,
            'origin_domain' => $state_payload->domain,
            'action' => $state_payload->action,
            'mode' => $state_payload->mode,
        ) + (array) $framework_user;
        $verify_token = json_decode(hbm_encode_transient_jwt($verify_payload, '', 'hbm-auth-access-'));
        $verify_token_urlcoded = urlencode($verify_token->jwt);
        $redirect_url = "{$state_payload->domain}/wp-json/hbm-auth-client/v1/validate_token?state={$state_urlcoded}&access_code={$verify_token_urlcoded}";

        if ($state_payload->mode == 'test') {

            $message = "<h3>You are on the SSO Server</h3>"
                . "<p>Authentication from {$framework_context->label} received: </p><pre>" . json_encode($state_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "</pre>"
                . "<p>{$framework_context->label} user: </p><pre>" . json_encode($verified_user, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . '</pre>'
                . "<p>WP user: </p><pre>" . json_encode($framework_user, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . '</pre>';
            hbm_echo_modal($redirect_url, $message);
        } else {
            hbm_set_headers();
            echo "<script type='text/javascript'>    window.location.href = '{$redirect_url}}';</script>";
        }
    }


    public function initiate_callback(\WP_REST_Request $request)
    {
        $state_urlcoded = $request->get_param('state');
        $state = urldecode($state_urlcoded);
        $state_payload = hbm_extract_payload($state);
        $application = $this->get_application($state_payload->domain);
        if (!$application) {
            new \WP_Error('no_site', 'No site found', array('status' => 400));
        }
        $framework_api = $this->init_auth_framework($application);
        $redirect_url = $this->get_redirect_url($state_payload->action, $application);

        $initiate_endpoint = $framework_api->create_auth_endpoint($state_payload->action, $redirect_url, $state_urlcoded, $application);
        if ($state_payload->action == 'logout') {
            $instance =  HBM_SSO_User_Session::get_instance($application['application_uid']);
            $instance->set_sso_logout($state_urlcoded);
        }
        if ($state_payload->mode == 'test') {
            $message = "<h3>You are on the SSO Server (first time)</h3>"
                . "<p>Initatiate request received: </p><pre>" . json_encode($state_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "</pre>";
            hbm_echo_modal($initiate_endpoint, $message);
        } else {
            hbm_set_headers();
            echo "<script type='text/javascript'>window.location.href = '{$initiate_endpoint}';</script>";
        }
    }

    public function handle_framework_logout(\WP_REST_Request $request)
    {
        $application = $request->get_param('app');
        if (!isset($application)) {
            return new \WP_Error('no_app', 'No application on logout', array('status' => 400));
        }
        $user_session =  HBM_SSO_User_Session::get_instance($application);
        $state = $user_session->get_sso_logout();
        if (!isset($state)) {
            return new \WP_Error('no_state', 'No state received', array('status' => 400));
        }
        $state_payload = hbm_extract_payload($state);
        $mode = $state_payload->mode;
        $logout_url = "{$state_payload->domain}/wp-json/hbm-auth-client/v1/logout-client?state={$state}";
        $user_session->logout_sso_user();
        if ($mode == 'test') {
            $message = "<h3>You are BACK on the SSO Server</h3>"
                . "<p>Logout request received: </p><pre>" . json_encode($state_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "</pre>";
            hbm_echo_modal($logout_url, $message);
        } else {
            hbm_set_headers();
            echo "<script type='text/javascript'>window.location.href = '{$logout_url}';</script>";
        }
    }

    public function callback_set_sso(\WP_REST_Request $request)
    {
        $state_urlcoded = $request->get_param('state');
        $state = urldecode($state_urlcoded);
        $state_payload = hbm_extract_payload($state);

        $access_code_urlcoded = $request->get_param('access_code');
        $access_code = urldecode($access_code_urlcoded);
        $framework_user = hbm_decode_transient_jwt($access_code);
        $application = $this->get_application($state_payload->domain);
        if (!$application) {
            new \WP_Error('no_site', 'No site found', array('status' => 400));
        }
        $framework_api = $this->init_auth_framework($application);

        $framework_context = $framework_api->get_framework_context();
        $sso_user_urlcoded = $request->get_param('sso_user');
        $sso_user_jwt = urldecode($sso_user_urlcoded);
        $sso_user_received = hbm_extract_payload($sso_user_jwt);
        $sso_user = array_merge((array) $sso_user_received, (array) $state_payload);
        $user_session =  HBM_SSO_User_Session::get_instance($application['application_uid']);
        $user_session_data = (array) $sso_user_received;
        unset($user_session_data['identifier']);
        $user_session->set_sso_user(
            array(
                'sso_user' =>     $user_session_data
            )
        );
        if ($state_payload->mode == 'test') {

            $message = "<h3>You are BACK on the SSO Server</h3>"
                . "<p>Access Code is decoded and valid: </p><pre>" . json_encode($state_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "</pre>"
                . "<p>{$framework_context->label} user: </p><pre>" . json_encode($framework_user, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . '</pre>'
                . "<p>SSO user: </p><pre>" . json_encode($sso_user, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . '</pre>';
            hbm_echo_modal(null, $message);
        }
    }

    function get_redirect_url($action, $application)
    {
        $settings = \HBM\hbm_fetch_pods_act('hbm-auth-server');
        if ($settings['test_server']) {
            $sso_server = $settings['test_domain'];
        } else {
            $sso_server = hbm_get_current_domain() . '/';
        }
        if ($action == 'logout') {
            $redirect_url = $sso_server . 'wp-json/hbm-auth-server/v1/framework_logout?app=' . $application['application_uid']; // Construct the logout URL based on the sso server's domain
        } else {
            $redirect_url = $sso_server . 'wp-json/hbm-auth-server/v1/callback'; // Construct the redirect URL based on the sso server's domain
        }
        return $redirect_url;
    }
}
