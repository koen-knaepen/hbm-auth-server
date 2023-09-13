<?php

/**
 * 
 * 
 * 
 * 
 */

class HBM_Framework_Cognito
{

   private $framework_choosen;

   public function __construct()
   {
      $this->framework_choosen = get_option('_hbm-auth-framework');
      if ($this->framework_choosen != 'cognito') {
         return;
      }
      add_filter("hbm_create_auth_endpoint", array($this, 'create_auth_endpoint'), 10, 4);
      add_filter("hbm_get_access_code", array($this, 'exchange_code_for_tokens'), 10, 3);
      add_filter("hbm_get_wp_user_data", array($this, 'transform_to_wp_user'), 10, 2);
      add_filter("hbm_get_framework_context", array($this, 'get_framework_context'), 10, 1);
   }

   public function get_framework_context($default_value)
   {
      $context = array(
         'name' => 'cognito',
         'label' => 'Cognito',
         'metadata' => "hbm_cognito_id",
         'auth_id_name' => 'sub',
      );
      return (object) $context;
   }
   public function create_auth_endpoint($default_value, $action, $redirect_url, $jwt)
   {
      $endpoint = "";
      $userpool_sub_domain = get_option("_hbm-cognito-auth-userpool-domain");
      $userpool_regio = get_option("_hbm-cognito-auth-regio");
      $client_id = get_option("_hbm-cognito-auth-client-id");

      switch ($action) {
         case 'login':
            $endpoint =
               "https://{$userpool_sub_domain}.auth.{$userpool_regio}.amazoncognito.com/login?client_id={$client_id}&response_type=code&response_type=code&scope=email+openid+profile&redirect_uri={$redirect_url}&state={$jwt}";
            break;
         case 'signup':
            $endpoint =
               "https://{$userpool_sub_domain}.auth.{$userpool_regio}.amazoncognito.com/signup?client_id={$client_id}&response_type=code&response_type=code&scope=email+openid&redirect_uri=http://localhost/wp-json/hbm-auth/v1/callback&state={$jwt}";
            break;
         case 'logout':
            $endpoint =
               "https://{$userpool_sub_domain}.auth.{$userpool_regio}.amazoncognito.com/logout?client_id={$client_id}&logout_uri={$redirect_url}";
            break;
         default:
            break;
      }

      return $endpoint;
   }

   public function exchange_code_for_tokens($default_value, $code, $action)
   {
      $sso_server = get_option('_hbm-auth-sso-server-url');
      if ($sso_server == '') {
         $redirect_url = site_url('/wp-json/hbm-auth/callback'); // Construct the redirect URL based on the site's domain
      } else {
         $redirect_url = $sso_server . '/wp-json/hbm-auth/callback'; // Construct the redirect URL based on the site's domain
      }
      $userpool_region = get_option("_hbm-cognito-auth-regio");
      $client_id = get_option("_hbm-{$this->framework_choosen}-auth-client-id");
      $client_secret = get_option("_hbm-{$this->framework_choosen}-auth-client-secret");
      $userpool_domain = get_option("_hbm-cognito-auth-userpool-domain");

      // Construct the Cognito token endpoint
      $token_endpoint = "https://{$userpool_domain}.auth.{$userpool_region}.amazoncognito.com/oauth2/token";

      $args = array(
         'headers' => array(
            'Content-Type' => 'application/x-www-form-urlencoded',
         ),
         'body' => array(
            'grant_type' => 'authorization_code',
            'client_id' => $client_id,
            'client_secret' => $client_secret,
            'scope' => 'openid profile',
            'code' => $code,
            'redirect_uri' => $redirect_url
         )
      );

      $response = wp_remote_post($token_endpoint, $args);

      if (is_wp_error($response)) {
         return array('error' => 'request_failed', 'error_description' => $response->get_error_message());
      }

      $body = wp_remote_retrieve_body($response);
      $tokenData = json_decode($body, true);

      // If access token is present, fetch user info
      if (isset($tokenData['access_token'])) {
         $userInfo = $this->get_user_info($tokenData['access_token'], $userpool_domain, $userpool_region);
         $tokenData['user_info'] = $userInfo;
      }
      return $tokenData;
   }

   private function get_user_info($accessToken, $userpool_domain, $userpool_region)
   {
      $endpoint = "https://{$userpool_domain}.auth.{$userpool_region}.amazoncognito.com/oauth2/userInfo";

      $response = wp_remote_get($endpoint, [
         'headers' => [
            'Authorization' => "Bearer {$accessToken}"
         ]
      ]);

      if (is_wp_error($response)) {
         return array('error' => 'request_failed', 'error_description' => $response->get_error_message());
      }
      $body = wp_remote_retrieve_body($response);
      return json_decode($body, true);
   }

   public function transform_to_wp_user($default_value, $cognito_user)
   {
      $wp_user = array(
         'user_login' => $cognito_user->email,
         'email' => $cognito_user->email,
         'first_name' => $cognito_user->given_name,
         'last_name' => $cognito_user->family_name,
         'auth_id' => $cognito_user->sub,
         'display_name' => $cognito_user->given_name . ' ' . $cognito_user->family_name,
         'role' => 'subscriber',
         'framework' => 'cognito',
         'domain' => hbm_get_current_domain(),
      );
      return (object) $wp_user;
   }

}