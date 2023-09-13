<?php
// class-hbm-callback-api.php
/**
 * Summary of class-hbm-callback-api
 * This class is responsible for the callback functionality of the User-PW authentication
 * There cannot be any code that is dependent on the params in the database in this class
 * @package HBM_Auth
 * @subpackage api
 */


class HBM_Callback_Handler
{
    private $secret_manager;

    /**
     * Summary of _deprecated_constructor
     * 1. Register the callback endpoint
     */
    public function __construct($jwt_manager)
    {
        $this->secret_manager = $jwt_manager;
        add_action('rest_api_init', array($this, 'hbm_register_callback_endpoint'));
        add_filter('hbm_get_redirect_url', array($this, 'get_redirect_url'), 10, 2);
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
            'hbm-auth',
            '/callback',
            array(
                'methods' => 'GET',
                'callback' => array($this, 'hbm_handle_callback'),
                'permission_callback' => '__return_true'
            )
        );

        register_rest_route(
            'hbm-auth',
            '/initiate',
            array(
                'methods' => 'GET',
                'callback' => array($this, 'hbm_initiate_callback'),
                'permission_callback' => '__return_true'
            )
        );

        register_rest_route(
            'hbm-auth',
            '/framework_logout',
            array(
                'methods' => 'GET',
                'callback' => array($this, 'hbm_handle_framework_logout'),
                'permission_callback' => '__return_true'
            )
        );

        register_rest_route(
            'hbm-auth/v1',
            '/sso_status',
            array(
                'methods' => 'GET',
                'callback' => array($this, 'hbm_set_sso'),
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
    public function hbm_handle_callback(WP_REST_Request $request)
    {
        // Get the authorization code from Framework
        // $this->log_request($request);
        $state_urlcoded = $request->get_param('state');
        $state = urldecode($state_urlcoded);
        $state_payload = extract_payload($state);
        $code = $request->get_param('code');

        if (empty($code)) {
            return new WP_Error('no_code', 'No code received', array('status' => 400));
        }

        // Exchange the authorization code for tokens
        $tokens = apply_filters('hbm_get_access_code', '', $code, $state_payload->action);
        $framework_context = apply_filters('hbm_get_framework_context', '');

        if (isset($tokens['error'])) {
            return new WP_Error('token_error', $tokens['error'], array('status' => 400));
        }

        $id_token = $tokens['id_token'];
        $framework_user = extract_payload($id_token);
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
        $verify_token = json_decode($this->secret_manager->encode_jwt($verify_payload, 'hbm-auth-access-'));
        $verify_token_urlcoded = urlencode($verify_token->jwt);
        $redirect_url = "{$state_payload->domain}/wp-json/hbm-auth/v1/validate_token?state={$state_urlcoded}&access_code={$verify_token_urlcoded}";

        if ($state_payload->mode == 'test') {

            $message = "<h3>You are on the SSO Server</h3>"
                . "<p>Authentication from {$framework_context->label} received: </p><pre>" . json_encode($state_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "</pre>"
                . "<p>{$framework_context->label} user: </p><pre>" . json_encode($framework_user, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . '</pre>';
            echo_modal($redirect_url, $message);
        } else {
            hbm_set_headers();
            echo "<script type='text/javascript'>    window.location.href = '{$redirect_url}}';</script>";
        }
    }

    /**
     * * Summary of extract_payload
     * @param mixed $state is a JWT token
     * @return stdClass returns the payload of the JWT token (NON verified 
     */
    // private function extract_payload($state)
    // {
    //     list($header, $payload, $signature) = explode(".", $state);
    //     $decoded_payload = base64_decode($payload, false);
    //     return json_decode($decoded_payload);
    // }

    public function hbm_initiate_callback(WP_REST_Request $request)
    {
        $state_urlcoded = $request->get_param('state');
        $state = urldecode($state_urlcoded);
        $state_payload = extract_payload($state);
        $redirect_url = $this->get_redirect_url($state_payload->action);
        $initiate_endpoint = apply_filters('hbm_create_auth_endpoint', '', $state_payload->action, $redirect_url, $state_urlcoded);
        if ($state_payload->action == 'logout') {
            do_action(
                'hbm_set_sso_user',
                array(
                    'state' => $state_urlcoded,
                )
            );
        }
        if ($state_payload->mode == 'test') {
            $message = "<h3>You are on the SSO Server (first time)</h3>"
                . "<p>Initatiate request received: </p><pre>" . json_encode($state_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "</pre>"
            ;
            echo_modal($initiate_endpoint, $message);
        } else {
            hbm_set_headers();
            echo "<script type='text/javascript'>window.location.href = '{$initiate_endpoint}';</script>";
        }

    }

    public function hbm_handle_framework_logout(WP_REST_Request $request)
    {
        // check the refferer of the request
        $current_sso_user = apply_filters('hbm_get_sso_user', '');
        if (!isset($current_sso_user['state'])) {
            return new WP_Error('no_state', 'No state received', array('status' => 400));
        }
        $state = $current_sso_user['state'];
        $state_payload = extract_payload($state);
        $mode = $current_sso_user['mode'];
        $logout_url = "{$state_payload->domain}/wp-json/hbm-auth/v1/logout-client?state={$state}";
        do_action('hbm_logout_sso_user', $current_sso_user);
        if ($mode == 'test') {
            $message = "<h3>You are BACK on the SSO Server</h3>"
                . "<p>Logout request received: </p><pre>" . json_encode($current_sso_user, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "</pre>";
            echo_modal($logout_url, $message);
        } else {
            hbm_set_headers();
            echo "<script type='text/javascript'>window.location.href = '{$logout_url}';</script>";
        }

    }

    public function hbm_set_sso(WP_REST_Request $request)
    {
        $state_urlcoded = $request->get_param('state');
        $state = urldecode($state_urlcoded);
        $state_payload = extract_payload($state);
        $access_code_urlcoded = $request->get_param('access_code');
        $access_code = urldecode($access_code_urlcoded);
        $framework_user = $this->secret_manager->decode_jwt($access_code);
        $framework_context = apply_filters('hbm_get_framework_context', '');
        $sso_user_urlcoded = $request->get_param('sso_user');
        $sso_user_jwt = urldecode($sso_user_urlcoded);
        $sso_user_received = extract_payload($sso_user_jwt);
        $sso_user = array_merge((array) $sso_user_received, (array) $state_payload);
        do_action('hbm_set_sso_user', $sso_user);

        if ($state_payload->mode == 'test') {

            $message = "<h3>You are BACK on the SSO Server</h3>"
                . "<p>Access Code is decoded and valid: </p><pre>" . json_encode($state_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "</pre>"
                . "<p>{$framework_context->label} user: </p><pre>" . json_encode($framework_user, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . '</pre>'
                . "<p>SSO user: </p><pre>" . json_encode($sso_user, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . '</pre>'
            ;
            echo_modal(null, $message);
        }
    }

    function get_redirect_url($action)
    {
        $sso_server = get_option('_hbm-auth-sso-server-url');
        if ($action == 'logout') {
            $redirect_url = $sso_server . '/wp-json/hbm-auth/framework_logout'; // Construct the logout URL based on the sso server's domain
        } else {
            $redirect_url = $sso_server . '/wp-json/hbm-auth/callback'; // Construct the redirect URL based on the sso server's domain
        }
        return $redirect_url;
    }
}