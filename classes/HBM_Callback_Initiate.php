<?php

namespace HBM\auth_server;

use HBM\Instantiations\HBM_Class_Handler;
use function HBM\hbm_echo_modal;
use function HBM\hbm_set_headers;
use function HBM\hbm_extract_domain;
use function HBM\hbm_get_current_domain;
use HBM\Cookies_And_Sessions\HBM_Session;
use HBM\Plugin_Management\HBM_Plugin_Utils;
use HBM\Data_Handlers\HBM_Data_Helpers;
use HBM\Database_Sessions\Pods_Session_Factory;


/**
 * Summary of class-hbm-callback-api
 * This class is responsible for the callback functionality of the User-PW authentication
 * There cannot be any code that is dependent on the params in the database in this class
 * @package HBM_Auth
 * @subpackage api
 */


class HBM_Callback_Initiate extends HBM_Class_Handler
{

    use HBM_Session {
        browser_transient as private;
    }
    use HBM_Plugin_Utils;
    use HBM_Data_Helpers {
        hbm_extract_payload as private;
    }


    private $sso_user_session = null;
    private $transient = null;
    private object $pods_session;

    public function __construct()
    {
        $this->transient = $this->browser_transient();
        $this->transient->set_policy(false, 5 * \MINUTE_IN_SECONDS);
        $this->pods_session = Pods_Session_Factory::HBM()::get_instance();
        add_action('rest_api_init', array($this, 'hbm_register_endpoint'));
    }

    protected static function set_pattern(): array
    {
        return [
            'pattern' => 'singleton',
            '__ticket' =>
            ['Entry' => ['is_api', ['check_api_namespace', 'hbm-auth-server'], ['check_api_endpoint', 'initiate']]],
        ];
    }

    private function init_auth_framework($application)
    {
        $framework = $application['app_framework'];
        return HBM_Auth_Framework::get_instance($framework);
    }

    private function get_application($input_domain)
    {
        $application = $this->pods_session->HBM_pod('hbm-auth-server-site', 'application', ['name' => $input_domain])->get_raw_data();
        if ($application) {
            return $application;
        } else {
            throw new \Exception("No application found for domain {$input_domain}, please see the administrator");
        }
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
            "hbm-" . $this->hbm_sub_namespace(__NAMESPACE__, true) . '/v1',
            '/initiate',
            array(
                'methods' => 'GET',
                'callback' => array($this, 'initiate_callback'),
                'permission_callback' => '__return_true'
            )
        );
    }



    public function initiate_callback(\WP_REST_Request $request)
    {
        $state_urlcoded = $request->get_param('state');
        $state = urldecode($state_urlcoded);
        $state_payload = $this->hbm_extract_payload($state);
        $application = $this->get_application($state_payload['domain']);
        if (!$application) {
            new \WP_Error('no_site', 'No site found', array('status' => 400));
        }
        $framework_api = $this->init_auth_framework($application);
        $redirect_url = $this->get_redirect_url($state_payload['action'], $application);

        $initiate_endpoint = $framework_api->create_auth_endpoint($state_payload['action'], $redirect_url, $state_urlcoded, $application);
        if ($state_payload['action'] == 'logout') {
            $this->transient->set("sso_logout", $state_urlcoded);
        }
        if ($state_payload['mode'] == 'test') {
            $message = "<h3>You are on the SSO Server (first time)</h3>"
                . "<p>Initatiate request received: </p><pre>" . json_encode($state_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "</pre>";
            hbm_echo_modal($initiate_endpoint, $message);
        } else {
            hbm_set_headers();
            echo "<script type='text/javascript'>window.location.href = '{$initiate_endpoint}';</script>";
        }
    }


    private function get_redirect_url($action, $application)
    {
        $test_server = $this->pods_session->HBM_setting('hbm-auth-server', 'test_server')->get_raw_data();
        if ($test_server) {
            $sso_server = $this->pods_session->HBM_setting('hbm-auth-server', 'test_domain')->get_raw_data();
        } else {
            $sso_server = hbm_get_current_domain() . '/';
        }
        if ($action == 'logout') {
            $redirect_url = $sso_server . 'wp-json/hbm-auth-server/v1/framework_logout?app=' . $application['id']; // Construct the logout URL based on the sso server's domain
        } else {
            $redirect_url = $sso_server . 'wp-json/hbm-auth-server/v1/callback'; // Construct the redirect URL based on the sso server's domain
        }
        return $redirect_url;
    }
}
