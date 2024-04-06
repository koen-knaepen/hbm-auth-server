<?php

namespace HBM\auth_server;

use HBM\Instantiations\HBM_Class_Handler;
use HBM\Cookies_And_Sessions\HBM_Session;
use HBM\Data_Handlers\Data_Middleware\Data_JWT_Transformation;
use HBM\Plugin_Management\HBM_Plugin_Utils;
use HBM\Data_Handlers\HBM_JWT_Helpers;
use HBM\Database_Sessions\Pods_Session_Factory;
use HBM\Helpers\HBM_Timestamp;
use HBM\helpers\WP_Rest_Modal;
use HBM\Loader\Browser\Transients;

/**
 * Summary of class-hbm-callback-api
 * This class is responsible for the callback functionality of the User-PW authentication
 * There cannot be any code that is dependent on the params in the database in this class
 * @package HBM_Auth
 * @subpackage api
 */


class HBM_Callback_Initiate extends HBM_Class_Handler
{
    // use HBM_Session {
    //     browser_transient as private;
    // }
    // use HBM_JWT_Helpers {
    //     hbm_extract_payload as private;
    // }

    use WP_Rest_Modal {
        hbm_set_headers as private;
        hbm_echo_modal as private;
    }

    private $plugin_utils;
    private $user = null;
    private $user_transient = null;
    private  $sites;
    private  $settings;

    public function __construct()
    {
        $this->add_to_dna([$this, 'init_pofs']);
    }

    public function init_pofs()
    {
        $this->plugin_utils = HBM_Plugin_Utils::HBM()::get_instance();
        $this->sites = $this->pof('sites');
        $this->user_transient = $this->pof('userTransient');
        $this->settings = $this->pof('settings');
        add_action('rest_api_init', array($this, 'hbm_register_endpoint'));
    }

    protected static function set_pattern($options = []): array
    {
        $timestamp = HBM_Timestamp::HBM()::get_instance();
        $session_expire = $timestamp->timestamp('next-year', '23:59:59');
        error_log("Session expire: " . $session_expire);
        return [
            'pattern' => 'singleton',
            '__ticket' =>
            ['Entry' => ['is_api', ['check_api_namespace', 'hbm-auth-server'], ['check_api_endpoint', 'initiate']]],
            '__inject' => [
                'hbmAuthServerSettings?settings' => [
                    'WPSettings', [
                        'identifier' => 'HBM_AUTH_SERVER_SETTINGS',
                        'group' => 'hbm-auth-server_',
                    ]
                ],
                'authServerApplications?sites' => [
                    'pods', [
                        'identifier' => 'HBM_AUTH_SERVER_SITES',
                        'podName' => 'hbm-auth-server-site'
                    ]
                ],
                'ssoUser?apps' => [
                    'browser', [
                        'scope' => 'hbm-apps',
                        'sessionIdentifier' => 'HBM_APPS',
                        'globalSecretsIdentifier' => 'HBM_AUTH_APPS_SECRET',
                        'sessionExpire' => $session_expire,
                        'cookieOptions' => [
                            'sameSite' => 'Lax',
                            'httpOnly' => true,
                            'secure' => true,
                            'path' => '/',
                            'expire_time' => $session_expire,
                        ]
                    ]
                ],
                'user?userTransient' => [
                    'transientAttribute', [
                        'identifier' => 'USERS',
                        'browser' => 'browser:ssoUser'
                    ]
                ],
                'ssoUser?users' => [
                    'transientCodedFields', [
                        'identifier' => 'CODED_USERS',
                        'transientScope' => 'hbm-users',
                        'browser' => 'browser:ssoUser',
                        'sessionIdentifier' => 'CODED_TRANSIENT_USER_FIELDS',
                        'globalSecretsIdentifier' => 'HBM_AUTH_TRANSIENT_FIELDS',
                        'expire' => 7 * \DAY_IN_SECONDS
                    ]
                ]

            ]
        ];
    }

    private function init_auth_framework($application)
    {
        $framework = $application['app_framework'];
        return HBM_Auth_Framework::HBM(['framework' => $framework])::get_instance();
    }

    private function get_application($input_domain)
    {
        $site = $this->sites->findOne(['name' => $input_domain]);
        $application = $this->sites->fget($site, 'application');
        if ($application) {
            return $application;
        } else {
            throw new \Exception("No application found for domain {$input_domain}, please see the administrator");
        }
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
        $state_payload = Data_JWT_Transformation::spayload($state)['data'] ?? [];
        $domain = $state_payload['domain'] ?? null;
        $application = $this->get_application($domain);
        if (!$application) {
            new \WP_Error('no_site', 'No site found', array('status' => 400));
        }
        $framework_api = $this->init_auth_framework($application);
        $redirect_url = $this->get_redirect_url($state_payload['action'], $application);

        $initiate_endpoint = $framework_api->create_auth_endpoint($state_payload['action'], $redirect_url, $state_urlcoded, $application);
        if ($state_payload['action'] == 'logout') {
            $this->user_transient->set("sso_logout", $state_urlcoded);
        }
        if ($state_payload['mode'] == 'test') {
            $message = "<h3>You are on the SSO Server (first time)</h3>"
                . "<p>Initatiate request received: </p><pre>" . json_encode($state_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "</pre>";
            $this->hbm_echo_modal($initiate_endpoint, $message);
        } else {
            $this->hbm_set_headers();
            echo "<script type='text/javascript'>window.location.href = '{$initiate_endpoint}';</script>";
        }
    }


    private function get_redirect_url($action, $application)
    {
        $test_server = $this->settings->fuse('test_server', false);
        if ($test_server) {
            $sso_server = $this->settings->fuse('test_domain');
        } else {
            $sso_server = \home_url() . '/';
        }
        if ($action == 'logout') {
            $redirect_url = $sso_server . 'wp-json/hbm-auth-server/v1/framework_logout?app=' . $application['id']; // Construct the logout URL based on the sso server's domain
        } else {
            $redirect_url = $sso_server . 'wp-json/hbm-auth-server/v1/callback'; // Construct the redirect URL based on the sso server's domain
        }
        return $redirect_url;
    }
}
