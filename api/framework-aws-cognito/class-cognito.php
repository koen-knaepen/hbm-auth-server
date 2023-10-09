<?php

namespace HBM\auth_server;

require_once HBM_PLUGIN_PATH . 'api/class-abstract-framework.php';
require_once HBM_MAIN_UTIL_PATH . 'pods-act.php';

class HBM_Framework_Cognito extends HBM_Auth_Framework
{

   private $framework_choosen;


   protected function set_context(): array
   {
      $context = array(
         'name' => 'cognito',
         'label' => 'Cognito',
         'auth_id_name' => 'sub',
      );
      return  $context;
   }
   public function create_auth_endpoint($action, $redirect_url, $jwt, $application)
   {
      $endpoint = "";
      $userpool_sub_domain = $application['cognito_userpool'];
      $userpool_regio = $application['cognito_aws_regio'];
      $client_id = $application['framework_client_id'];

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

   public function exchange_code_for_tokens($code, $application)
   {
      $settings = \hbm_fetch_pods_act('hbm-auth-server');
      if ($settings['test_server']) {
         $sso_server = $settings['test_domain'];
      } else {
         $sso_server = hbm_get_current_domain() . '/';
      }
      $redirect_url = $sso_server . 'wp-json/hbm-auth-server/v1/callback'; // Construct the redirect URL based on the site's domain
      error_log("redirect_url: " . $redirect_url);
      $userpool_region = $application['cognito_aws_regio'];
      $client_id = $application['framework_client_id'];
      $client_secret = $application['framework_client_secret'];
      $userpool_domain = $application['cognito_userpool'];

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

   public function transform_to_wp_user($cognito_user)
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
