<?php

require_once HBM_PLUGIN_PATH . 'public/class-hbm-shortcodes.php'; // Shortcodes for the public-facing side of the site
require_once HBM_PLUGIN_PATH . 'admin/class-hbm-auth-admin.php'; // Admin-related functionality
require_once HBM_PLUGIN_PATH . 'api/class-hbm-api.php'; // API-related functionality
require_once HBM_PLUGIN_PATH . 'public/class-hbm-sso-user.php'; // sso user class
require_once HBM_PLUGIN_PATH . 'includes/class-hbm-auth-activate.php'; // Activation-related functionality
require_once HBM_PLUGIN_PATH . 'includes/class-hbm-auth-deactivate.php'; // Deactivation-related functionality

class HBM_Server_Auth
{
    private $admin;
    private $plugin_file;
    private $api;
    private $activate;
    private $deactivate;
    private $sso_user;
    private $log_requests = false;
    private $log_simple_requests = true;

    public function __construct($file)
    {
        // Constructor code here

        /* Check if WP_DEBUG is enabled and if the log_requests option is enabled
         Log the request headers if the log_requests option is enabled */

        if (defined('WP_DEBUG') && WP_DEBUG && $this->log_requests) {
            if ($this->log_requests) {
                add_action('http_api_debug', array($this, 'log_get_request_headers'), 10, 5);
                add_action('init', array($this, 'log_get_normal_headers'));
            }
            if ($this->log_simple_requests) {
                add_action('init', 'log_incoming_requests');
            }
            hbm_error_log('WP_DEBUG is enabled');
        }

        // Check if the request is relevant
        if (!$this->relevant_headers()) {
            if ($this->log_requests) {
                error_log('Request is not relevant');
            }
            return;
        }

        $current_domain = hbm_get_current_domain();
        if ($current_domain == get_option('_hbm-auth-sso-server-url')) {
            session_start();
            $this->sso_user = new HBM_SSO_User();
        }
        $this->plugin_file = $file;
        if (is_admin()) {
            hbm_error_log('Loading hbm-auth plugin for admin...');
            $this->admin = new HBM_Server_Auth_Admin();
            $this->activate = new HBM_Auth_Activate();
            $this->deactivate = new HBM_Auth_Deactivate();
        } else {
            hbm_error_log('Loading hbm-auth plugin for public...');
        }
        $this->api = new HBM_Server_API();
    }

    public function log_get_request_headers($response, $context, $class, $parsed_args, $url)
    {
        error_log('log_get_api_headers' . print_r($parsed_args, true));
        // Check if it's a GET request
        if ($parsed_args['method'] === 'GET') {
            // Log the request headers
            error_log('GET request to: ' . $url);
            error_log('Request Headers: ' . print_r($parsed_args['headers'], true));

            // If you also want to log the response headers:
            if (!is_wp_error($response)) {
                error_log('Response Headers: ' . print_r(wp_remote_retrieve_headers($response), true));
            }
        }
    }

    public function log_get_normal_headers()
    {
        $headers = ['HTTP_ACCEPT', 'REQUEST_URI'];
        error_log('log_get_normal_headers');


        if (count($headers) > 0) {
            $print_headers = array();
            foreach ($headers as $name) {
                // Check if the header exists in the $_SERVER superglobal
                if (isset($_SERVER[$name])) {
                    $print_headers[$name] = $_SERVER[$name];
                } else {
                    $print_headers[$name] = 'Header not set';
                }
            }
            error_log('Request Headers: ' . print_r($print_headers, true));
        } else {
            // Log the request headers
            error_log('Request Headers: ' . print_r($_SERVER, true));
        }
    }
    function relevant_headers()
    {
        $allowed_types = ['application/json', 'text/html', 'application/signed-exchange', 'text/html', 'application/xhtml+xml', 'application/xml', 'text/javascript'];

        if (isset($_SERVER['HTTP_ACCEPT'])) {
            $accept_header = $_SERVER['HTTP_ACCEPT'];

            $is_relevant = false;
            foreach ($allowed_types as $type) {
                if (strpos($accept_header, $type) !== false) {
                    $is_relevant = true;
                    break;
                }
            }

            return $is_relevant;  // Exit early for non-relevant requests

            // Execute the plugin script
        }
    }


    function log_incoming_requests()
    {
        $log_file = HBM_PLUGIN_ACCESS_LOG; // Change this to a writable path on your server
        $request_uri = $_SERVER['REQUEST_URI'];
        $request_method = $_SERVER['REQUEST_METHOD'];
        if (strpos($request_uri, 'hbm-entra-auth') !== false) {
            // Get the request body
            $request_body = file_get_contents('php://input');
            $request_body_formatted = json_encode($request_body, JSON_PRETTY_PRINT);
            $headers = getallheaders();
            $headers_formatted = json_encode($headers, JSON_PRETTY_PRINT);
            $log_entry = "{$request_method} - {$request_uri} - Headers: {$headers_formatted} - Body: {$request_body_formatted}\n";
        } else {
            $log_entry = "{$request_method} - {$request_uri}\n";
        }
        error_log($log_entry);
    }
}
