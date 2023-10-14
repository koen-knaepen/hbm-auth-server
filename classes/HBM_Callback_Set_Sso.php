<?php

namespace HBM\auth_server;

use function HBM\hbm_extract_payload;
use function HBM\hbm_echo_modal;
use function HBM\hbm_extract_domain;
use function HBM\hbm_sub_namespace;
use function HBM\hbm_decode_transient_jwt;
use \HBM\HBM_User_Session;

/**
 * Summary of class-hbm-callback-api
 * This class is responsible for the callback functionality of the User-PW authentication
 * There cannot be any code that is dependent on the params in the database in this class
 * @package HBM_Auth
 * @subpackage api
 */


class HBM_Callback_Set_Sso
{

    /**
     * Summary of _deprecated_constructor
     * 1. Register the callback endpoint
     */

    private $sso_user_session = null;
    public function __construct()
    {
        add_action('rest_api_init', array($this, 'hbm_register_endpoint'));
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
        $application = $sites[0]['application'];
        $this->sso_user_session = HBM_User_Session::get_instance($application['application_uid']);
        return $application;
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
    public function hbm_register_endpoint()
    {
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
        $user_session_data = (array) $sso_user_received;
        unset($user_session_data['identifier']);
        $this->sso_user_session->set_sso_user($user_session_data);
        if ($state_payload->mode == 'test') {

            $message = "<h3>You are BACK on the SSO Server</h3>"
                . "<p>Access Code is decoded and valid: </p><pre>" . json_encode($state_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "</pre>"
                . "<p>{$framework_context->label} user: </p><pre>" . json_encode($framework_user, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . '</pre>'
                . "<p>SSO user: </p><pre>" . json_encode($sso_user, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . '</pre>';
            hbm_echo_modal(null, $message);
        }
    }
}
