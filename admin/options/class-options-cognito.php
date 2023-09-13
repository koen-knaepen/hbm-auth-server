<?php

class HBM_Auth_Admin_Options_Cognito
{

    public function __construct()
    {
        $framework_option = get_option('_hbm-auth-framework'); // Retrieve the framework from the main options;
        if ($framework_option != 'cognito') {
            return;
        }

        // add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_cognito_assets')); // Enqueue the admin assets
        add_action('wp_ajax_test_framework', array($this, 'test_cognito_config_callback')); // AJAX callback to test the cognito configuration

    }

    public function enqueue_admin_cognito_assets()
    {
        // 1. Enqueue the admin JavaScripts
    }

    public function displayFields()
    {
        $framework = get_option('_hbm-auth-framework'); // Retrieve the framework from the main options;
        if ($framework != 'cognito') {
            return array();
            // error_log('Framework is not cognito. Not displaying cognito configuration options.');
        }
        $init_spinner = init_spinner('cognito', 'testFramework', 'test_framework', 'Click this button to test the cognito configuration', 'Test cognito Configuration', 'cognito Configuration is valid.');
        $fields = array(
            \Carbon_Fields\Field::make('text', 'hbm-cognito-auth-userpool-domain', 'Cognito Domain Userpool') // Field for the tenant name in cognito B2C
                ->set_help_text('Give the subdomain (first part) of the Cognito User Pool you want to use'),
            \Carbon_Fields\Field::make('text', 'hbm-cognito-auth-regio', 'AWS Regio') // Field for the tenant ID (not used at this moment) in cognito B2C
                ->set_help_text('Give the technical name of the AWS regio where the User Pool is hosted'),
            \Carbon_Fields\Field::make('text', 'hbm-cognito-auth-client-id', 'Application (Client) ID') // Field for the client ID in cognito B2C
                ->set_help_text('Give your client ID from the application in Cognito you want to use'),
            \Carbon_Fields\Field::make('text', 'hbm-cognito-auth-client-secret', 'Client Secret') // Field for the client secret in cognito B2C
                ->set_attribute('type', 'password') // Hide the actual value for security reasons
                ->set_help_text('Copy the secret you want to use from the application in cognito and paste it here'),
        );

        return array_merge($fields, $init_spinner);
    }

    /**
     * Summary of test_cognito_config_callback
     * @return void
     */

    public function test_cognito_config_callback()
    {
        // Perform the test using the cognito configuration parameters
        $tenant_name = get_option('_hbm-cognito-auth-tenant-name');
        $policy_name = get_option('_hbm-cognito-auth-login-policy');

        // Use the Azure AD B2C metadata endpoint for testing
        $metadata_endpoint = "https://{$tenant_name}.b2clogin.com/{$tenant_name}.onmicrosoft.com/{$policy_name}/v2.0/.well-known/openid-configuration";
        $response = wp_remote_get($metadata_endpoint);

        if (is_wp_error($response)) {
            wp_send_json_error(array('message' => 'Failed to connect to Azure. Please check your configuration.'));
        } elseif (wp_remote_retrieve_response_code($response) != 200) {
            wp_send_json_error(array('message' => 'Invalid cognito Configuration. Please check the provided details.'));
        } else {
            wp_send_json_success();
        }
    }

}