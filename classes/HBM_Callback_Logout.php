<?php

namespace HBM\auth_server;

use HBM\Instantiations\HBM_Class_Handler;
use function HBM\hbm_extract_payload;
use function HBM\hbm_echo_modal;
use function HBM\hbm_set_headers;
use function HBM\hbm_sub_namespace;

/**
 * Summary of class-hbm-callback-api
 * This class is responsible for the callback functionality of the User-PW authentication
 * There cannot be any code that is dependent on the params in the database in this class
 * @package HBM_Auth
 * @subpackage api
 */


class HBM_Callback_Logout extends HBM_Class_Handler
{

    use \HBM\Cookies_And_Sessions\HBM_Session {
        browser_transient as private;
        user_session as private;
    }

    private $transient;
    private $sso_user_session;
    /**
     * Summary of _deprecated_constructor
     * 1. Register the callback endpoint
     */

    public function __construct()
    {
        $this->transient = $this->browser_transient();
        $this->sso_user_session = $this->user_session();
        add_action('rest_api_init', array($this, 'hbm_register_endpoint'));
    }

    protected static function set_pattern(): array
    {
        return [
            'pattern' => 'singleton',
            '__ticket' =>
            ['Entry' => ['is_api', ['check_api_namespace', 'hbm-auth-server'], ['check_api_endpoint', 'framework_logout']]],
        ];
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
            '/framework_logout',
            array(
                'methods' => 'GET',
                'callback' => array($this, 'handle_framework_logout'),
                'permission_callback' => '__return_true'
            )
        );
    }

    public function handle_framework_logout(\WP_REST_Request $request)
    {
        $application = $request->get_param('app');
        if (!isset($application)) {
            return new \WP_Error('no_app', 'No application on logout', array('status' => 400));
        }
        $this->sso_user_session->set_application($application);
        $state = $this->transient->get('sso_logout');
        if (!isset($state)) {
            return new \WP_Error('no_state', 'No state received', array('status' => 400));
        }
        $state_payload = hbm_extract_payload($state);
        $mode = $state_payload->mode;
        $logout_url = "{$state_payload->domain}/wp-json/hbm-auth-client/v1/logout-client?state={$state}";
        $this->sso_user_session->logout_sso_user();
        $this->transient->delete('sso_logout');
        if ($mode == 'test') {
            $message = "<h3>You are BACK on the SSO Server</h3>"
                . "<p>Logout request received: </p><pre>" . json_encode($state_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "</pre>";
            hbm_echo_modal($logout_url, $message);
        } else {
            hbm_set_headers();
            echo "<script type='text/javascript'>window.location.href = '{$logout_url}';</script>";
        }
    }
}
