<?php

class HBM_Auth_Admin_Options_Entra
{

    public function __construct()
    {
        $framework_option = get_option('_hbm-auth-framework'); // Retrieve the framework from the main options;
        if ($framework_option != 'entra') {
            return;
        }

        // add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_entra_assets')); // Enqueue the admin assets
        add_action('wp_ajax_test_framework', array($this, 'test_entra_config_callback')); // AJAX callback to test the Entra configuration

    }

    public function enqueue_admin_entra_assets()
    {
        // 1. Enqueue the admin JavaScripts
    }

    public function displayFields()
    {
        $framework = get_option('_hbm-auth-framework'); // Retrieve the framework from the main options;
        if ($framework != 'entra') {
            return array();
            // error_log('Framework is not Entra. Not displaying Entra configuration options.');
        }
        $sso_server = get_option('_hbm-auth-sso-server-url');
        if ($sso_server == '') {
            $redirect_url = site_url('/wp-json/hbm-auth/callback'); // Construct the redirect URL based on the site's domain
        } else {
            $redirect_url = $sso_server . '/wp-json/hbm-auth/callback'; // Construct the redirect URL based on the site's domain
        }
        $init_spinner = init_spinner('entra', 'testFramework', 'test_framework', 'Click this button to test the Entra configuration', 'Test Entra Configuration', 'entra Configuration is valid.');
        $fields = array(
            \Carbon_Fields\Field::make('text', 'hbm-entra-auth-tenant-name', 'Tenant Name') // Field for the tenant name in Entra B2C
                ->set_help_text('Give the name of the Tenant in Entra you want to use'),
            \Carbon_Fields\Field::make('text', 'hbm-entra-auth-tenant-id', 'Tenant ID') // Field for the tenant ID (not used at this moment) in Entra B2C
                ->set_help_text('Give the technical name of the Tenant in Entra you want to use'),
            \Carbon_Fields\Field::make('text', 'hbm-entra-auth-client-id', 'Application (Client) ID') // Field for the client ID in Entra B2C
                ->set_help_text('Give your client ID from the application in Entra you want to use'),
            \Carbon_Fields\Field::make('text', 'hbm-entra-auth-client-secret', 'Client Secret') // Field for the client secret in Entra B2C
                ->set_attribute('type', 'password') // Hide the actual value for security reasons
                ->set_help_text('Copy the secret you want to use from the application in Entra and paste it here'),
            \Carbon_Fields\Field::make('text', 'hbm-entra-auth-signup-policy', 'Signup Policy') // Field for the policy name in Entra B2C
                ->set_help_text('Give the name of the Tenant in Entra you want to use'),
            \Carbon_Fields\Field::make('text', 'hbm-entra-auth-login-policy', 'Login Policy') // Field for the policy name in Entra B2C
                ->set_help_text('Give the name of the Tenant in Entra you want to use'),
            \Carbon_Fields\Field::make('text', 'hbm-entra-auth-profile-policy', 'Edit Profile Policy') // Field for the policy name in Entra B2C
                ->set_help_text('Give the name of the Tenant in Entra you want to use'),
        );
        return array_merge($fields, $init_spinner);
    }

    /**
     * Summary of test_entra_config_callback
     * @return void
     */

    public function test_entra_config_callback()
    {
        // Perform the test using the Entra configuration parameters
        $tenant_name = get_option('_hbm-entra-auth-tenant-name');
        $policy_name = get_option('_hbm-entra-auth-login-policy');

        // Use the Azure AD B2C metadata endpoint for testing
        $metadata_endpoint = "https://{$tenant_name}.b2clogin.com/{$tenant_name}.onmicrosoft.com/{$policy_name}/v2.0/.well-known/openid-configuration";
        $response = wp_remote_get($metadata_endpoint);

        if (is_wp_error($response)) {
            wp_send_json_error(array('message' => 'Failed to connect to Azure. Please check your configuration.'));
        } elseif (wp_remote_retrieve_response_code($response) != 200) {
            wp_send_json_error(array('message' => 'Invalid Entra Configuration. Please check the provided details.'));
        } else {
            wp_send_json_success();
        }
    }

}