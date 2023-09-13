<?php

/**
 * 
 * 
 * 
 * 
 */

class HBM_Framework_Entra
{

    private $framework_choosen;

    public function __construct()
    {
        $this->framework_choosen = get_option('_hbm-auth-framework');
        add_filter("hbm_create_auth_endpoint", array($this, 'create_auth_endpoint'), 10, 3);
        add_filter("hbm_get_access_code", array($this, 'exchange_code_for_tokens'), 10, 3);
        add_filter("hbm_get_wp_user_data", array($this, 'transform_to_wp_user'), 10, 2);
    }

    public function create_auth_endpoint($default_value, $action, $jwt)
    {

        $tenant_name = get_option("_hbm-{$this->framework_choosen}-auth-tenant-name");
        $tenant_id = get_option("_hbm-{$this->framework_choosen}-auth-tenant-id");
        $client_id = get_option("_hbm-{$this->framework_choosen}-auth-client-id");

        if ($action == 'signup') {
            $policy = get_option("_hbm-{$this->framework_choosen}-auth-signup-policy");
            $endpoint =
                "https://{$tenant_name}.b2clogin.com/{$tenant_id}/oauth2/v2.0/authorize?p={$policy}&client_id={$client_id}&nonce=defaultNonce&redirect_uri=http://localhost/wp-json/hbm-auth/v1/callback&state={$jwt}&scope=openid&response_type=code&prompt=login";
        }
        if ($action == 'login') {
            $policy = get_option("_hbm-{$this->framework_choosen}-auth-login-policy");
            $endpoint =
                "https://{$tenant_name}.b2clogin.com/{$tenant_id}/oauth2/v2.0/authorize?p={$policy}&client_id={$client_id}&nonce=defaultNonce&redirect_uri=http://localhost/wp-json/hbm-auth/v1/callback&state={$jwt}&scope=openid&response_type=code&prompt=login";
        }
        if ($action == 'logout') {
            $endpoint = null;
        }


        return $endpoint;
    }

    public function exchange_code_for_tokens($default_value, $code, $action)
    {

        $client_id = get_option("_hbm-{$this->framework_choosen}-auth-client-id");
        $client_secret = get_option("_hbm-{$this->framework_choosen}-auth-client-secret");
        $tenant_name = get_option("_hbm-{$this->framework_choosen}-auth-tenant-name");

        if ($action == 'signup') {
            $policy_name = get_option("_hbm-{$this->framework_choosen}-auth-signup-policy");
        }
        if ($action == 'login') {
            $policy_name = get_option("_hbm-{$this->framework_choosen}-auth-login-policy");
        }
        $token_endpoint = "https://{$tenant_name}.b2clogin.com/{$tenant_name}.onmicrosoft.com/{$policy_name}/oauth2/v2.0/token";

        $args = array(
            'body' => array(
                'client_id' => $client_id,
                'scope' => 'openid profile',
                'code' => $code,
                'redirect_uri' => site_url('/wp-json/hbm-auth/v1/callback'),
                'grant_type' => 'authorization_code',
                'client_secret' => $client_secret
            )
        );

        $response = wp_remote_post($token_endpoint, $args);

        if (is_wp_error($response)) {
            return array('error' => 'request_failed', 'error_description' => $response->get_error_message());
        }

        $body = wp_remote_retrieve_body($response);
        return json_decode($body, true);
    }

    public function transform_to_wp_user($default_value, $azure_user)
    {
        $wp_user = array(
            'user_login' => $azure_user['emails'][0],
            'user_email' => $azure_user['emails'][0],
            'first_name' => $azure_user['given_name'],
            'last_name' => $azure_user['family_name'],
            'entra_id' => $azure_user['oid'],
            'display_name' => $azure_user['given_name'] . ' ' . $azure_user['family_name'],
            'role' => 'subscriber'
        );
        return $wp_user;
    }

}