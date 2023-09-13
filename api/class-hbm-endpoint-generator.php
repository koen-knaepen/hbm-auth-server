<?php

/**
 * class-hbm-endpoint-generator.php
 * This class is responsible for the initiation of the authentication process
 * In a client - master configuration, this class should not have the Azure params and the secret keys in the database
 * @todo remove the direct read in the database of the Azure params and the secret keys
 * @package HBM_Auth
 * @subpackage api
 */

class HBM_Endpoint_Generator
{
    private $secret_manager;
    /**
     * Summary of _deprecated_constructor
     * 1. Register the callback endpoint
     */
    public function __construct($jwt_manager)
    {
        $this->secret_manager = $jwt_manager;
        add_action('rest_api_init', array($this, 'hbm_register_generator_endpoint'));
        add_action('hbm_auth_new_action', array($this, 'hbm_logoperation'), 10, 2);
        add_action('wp_ajax_hbm_renew_nonce', array($this, 'renew_nonce_callback'));
        add_action('wp_ajax_nopriv_hbm_renew_nonce', array($this, 'renew_nonce_callback'));
    }

    function renew_nonce_callback()
    {
        // Generate a new nonce
        $new_nonce = wp_create_nonce('wp_rest');

        // Return the new nonce via echo
        echo $new_nonce;

        // Terminate the AJAX request using wp_die()
        wp_die();
    }
    /**
     * Summary of hbm_register_callback_endpoint
     * Register the callback endpoints
     */
    public function hbm_register_generator_endpoint()
    {
        // register route to get the endpoints
        register_rest_route(
            'hbm-auth/v1',
            '/get_endpoint',
            array(
                'methods' => 'POST',
                'callback' => array($this, 'create_endpoint'),
                'permission_callback' => array($this, 'check_nonce_permission')
            )
        );
    }

    public function check_nonce_permission()
    {
        // Get the nonce from the request header
        $nonce = isset($_SERVER['HTTP_X_WP_NONCE']) ? $_SERVER['HTTP_X_WP_NONCE'] : '';

        // Verify the nonce
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new WP_Error('rest_forbidden', 'Nonce is invalid', array('status' => 403));
        }

        return true;
    }

    /**
     * Summary of the create_endpoint
     * This function is responsible for the creation of the endpoint
     * This endpoint is used by the login and logout buttons in the shortcode section
     * @todo remove the direct read in the database of the Azure params and the secret keys
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function create_endpoint(WP_REST_Request $request)
    {
        $endpoint = '';
        $sso_server = get_option('_hbm-auth-sso-server-url');
        if ($sso_server == '') {
            $sso_server = site_url();
        }
        $all_params = $request->get_params();
        $domain = hbm_get_current_domain();

        $state_payload = array_merge(
            $all_params,
            array(
                'domain' => $domain,
            )
        );
        // logout is a special case because the cycle is reversed
        $encoded_response = json_decode($this->secret_manager->encode_jwt($state_payload));
        $jwt = urlencode($encoded_response->jwt);
        $identifier = $encoded_response->identifier;

        // !!!!! We always need to pass first to the "internal SSO server !!!!!!
        $endpoint = "{$sso_server}/wp-json/hbm-auth/initiate?state={$jwt}";

        // Return the JWT token and any other data you need
        return new WP_REST_Response(array("endpoint" => $endpoint, "identifier" => $identifier), 200);
    }


    public function hbm_logoperation(mixed $user, $state_payload)
    {
        switch ($state_payload->action) {
            case 'login':
                $text = get_option('_hbm-auth-logout-button-text');
                if ($text == '') {
                    $text = 'Logout';
                }
                $text .= " " . $user->display_name;
                $newAction = 'logout';
                $class_list = 'hbm-auth-logout';
                $redirect_url = null;
                break;
            case 'logout':
                $text = get_option('_hbm-auth-login-button-text');
                if ($text == '') {
                    $text = 'Login';
                }
                $newAction = 'login';
                $class_list = 'hbm-auth-login';
                $redirect_url = null;
                break;
            default:
                $text = 'undefined';
                $newAction = 'undefined';
                $class_list = '';
                break;
        }
        $class_list .= " button button-primary";
        $cookieValue = array_merge(
            (array) $state_payload,
            array(
                'text' => $text,
                'action' => $newAction,
                'classList' => $class_list,
                'redirectUrl' => $redirect_url,
            )
        );
        setcookie('hbm_new_auth_action', json_encode($cookieValue), time() + 300, "/");
    }

    function custom_login_url($login_url, $redirect)
    {
        // Replace this with your custom login URL
        $custom_login_url = 'https://example.com/custom-login-page';

        // Modify the login URL based on your conditions if needed
        // For example, you can conditionally switch between default and custom URLs

        return $custom_login_url;
    }

}