<?php

require_once HBM_PLUGIN_PATH . 'api/class-hbm-encrypt.php'; // include an encryption management class
require_once HBM_PLUGIN_PATH . 'api/class-hbm-endpoint-generator.php'; // include the endpoint generator class
require_once HBM_PLUGIN_PATH . 'api/class-hbm-callback-handler.php'; // include the callback handler class
require_once HBM_PLUGIN_PATH . 'api/class-hbm-wp-authorization.php'; // include the authorization class

/**
 * 
 * 
 * 
 * 
 * 
 */

class HBM_API
{

    private $secret_manager;
    private $endpoint_generator;
    private $callback_handler;
    private $authorization;
    private $framework;

    public function __construct()
    {
        $this->set_framework();
        // add_action('wp', array($this, 'set_framework'), 2, 0);
        if (is_admin()) {
            add_action('admin_enqueue_scripts', array($this, 'enqueue_auth_generator_script')); // For back-end
        } else {
            add_action('wp_enqueue_scripts', array($this, 'enqueue_auth_generator_script')); // For front-end
        }


    }

    public function set_framework()
    {
        $framework_choosen = get_option('_hbm-auth-framework');
        switch ($framework_choosen) {
            case 'entra':
                require_once HBM_PLUGIN_PATH . 'api/framework-microsoft-entra/class-entra.php'; // Callback functionality for the Entra authentication
                $this->framework = new HBM_Framework_Entra();
                break;
            case 'cognito':
                require_once HBM_PLUGIN_PATH . 'api/framework-aws-cognito/class-cognito.php'; // Callback functionality for the Cognito authentication
                $this->framework = new HBM_Framework_Cognito();
                break;
            case 'google':
                require_once HBM_PLUGIN_PATH . 'api/framework-google-identity/class-google.php'; // Callback functionality for the Google Identity authentication
                $this->framework = new HBM_Framework_Google();
                break;
            default:
                error_log('No framework choosen');
                break;
        }
        $this->secret_manager = new HBM_JWT_Manager();

        $this->endpoint_generator = new HBM_Endpoint_Generator($this->secret_manager);
        $this->callback_handler = new HBM_Callback_Handler($this->secret_manager);
        $this->authorization = new HBM_WP_Authorization($this->secret_manager);

    }

    public function enqueue_auth_generator_script()
    {
        wp_enqueue_script('hbm-auth-handler', HBM_PLUGIN_URL . '/api/js/hbm-auth-handler.js', array('jquery'), '1.0.0', true);

        // Localize the script with new data
        $nonce = wp_create_nonce('wp_rest');
        wp_localize_script(
            'hbm-auth-handler',
            'hbmApiSettings',
            array(
                'endpointURL' => esc_url_raw(rest_url()) . 'hbm-auth/v1/get_endpoint',
                'nonce' => $nonce,
                'ajax_url' => admin_url('admin-ajax.php'),
            )
        );
    }



}