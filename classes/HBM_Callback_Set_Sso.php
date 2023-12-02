<?php

namespace HBM\auth_server;

use HBM\Instantiations\HBM_Class_Handler;
use function HBM\hbm_echo_modal;
use \HBM\Cookies_And_Sessions\HBM_Session;
use \HBM\Cookies_And_Sessions\HBM_State_Manager;
use \HBM\Plugin_Management\HBM_Plugin_Utils;
use HBM\Data_Handlers\HBM_Data_Helpers;
use HBM\Database_Sessions\Pods_Session_Factory;


/**
 * Summary of class-hbm-callback-api
 * This class is responsible for the callback functionality of the User-PW authentication
 * There cannot be any code that is dependent on the params in the database in this class
 * @package HBM_Auth
 * @subpackage api
 */


class HBM_Callback_Set_Sso extends HBM_Class_Handler
{
    use HBM_Session {
        browser_transient as private;
        user_session as private;
    }

    use HBM_Data_Helpers {
        hbm_extract_payload as private;
    }

    /**
     * Summary of _deprecated_constructor
     * 1. Register the callback endpoint
     */
    private $plugin_utils;
    private $sso_user_session = null;
    private $state_manager = null;
    private object $pods_session;
    public function __construct()
    {
        $this->plugin_utils = HBM_Plugin_Utils::HBM()::get_instance();
        $this->sso_user_session = $this->user_session();
        $this->state_manager = HBM_State_Manager::HBM()::get_instance();
        $this->pods_session = Pods_Session_Factory::HBM()::get_instance();
        add_action('rest_api_init', array($this, 'hbm_register_endpoint'));
    }

    protected static function set_pattern(): array
    {
        return [
            'pattern' => 'singleton',
            '__ticket' =>
            ['Entry' => ['is_api', ['check_api_namespace', 'hbm-auth-server'], ['check_api_endpoint', 'sso_status']]],
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
        if (!$application) {
            throw new \Exception("No application found for domain {$domain}, please see the administrator");
        }

        $this->sso_user_session->set_application($application['id']);
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
            "hbm-" . $this->plugin_utils->hbm_sub_namespace(__NAMESPACE__, true) . '/v1',
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
        $state_payload = $this->hbm_extract_payload($state);

        $access_code_urlcoded = $request->get_param('access_code');
        $access_code = urldecode($access_code_urlcoded);
        $framework_user = $this->state_manager->decode_transient_jwt($access_code);
        $application = $this->get_application($state_payload['domain']);
        if (!$application) {
            new \WP_Error('no_site', 'No site found', array('status' => 400));
        }
        $framework_api = $this->init_auth_framework($application);

        $framework_context = $framework_api->get_framework_context();
        $sso_user_urlcoded = $request->get_param('sso_user');
        $sso_user_jwt = urldecode($sso_user_urlcoded);
        $sso_user_received = $this->hbm_extract_payload($sso_user_jwt);
        $sso_user = array_merge($sso_user_received, $state_payload);
        $user_session_data = (array) $sso_user_received;
        unset($user_session_data['identifier']);
        $this->sso_user_session->set_sso_user($user_session_data);
        if ($state_payload['mode'] == 'test') {

            $message = "<h3>You are BACK on the SSO Server</h3>"
                . "<p>Access Code is decoded and valid: </p><pre>" . json_encode($state_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "</pre>"
                . "<p>{$framework_context->label} user: </p><pre>" . json_encode($framework_user, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . '</pre>'
                . "<p>SSO user: </p><pre>" . json_encode($sso_user, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . '</pre>';
            hbm_echo_modal(null, $message);
        }
    }
}
