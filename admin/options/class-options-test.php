<?php

class HBM_Auth_Admin_Options_Test
{

    public function __construct()
    {
        add_action('wp_ajax_clear_transient', array($this, 'clear_transient_callback')); // AJAX callback to test the Entra configuration
    }

    public function enqueue_admin_test_assets()
    {
        // 1. Enqueue the script to exchange the test button for the valid message


    }


    public function displayFields()
    {
        $spinner_fields = init_spinner(
            'cleanup',
            'clearTransient',
            'clear_transient',
            'Clikc this button to clear all fields for failed tests',
            'Clear test fields',
            'All transient fields cleared'
        );
        $framework_context = apply_filters('hbm_get_framework_context', '');
        $fields = array(
            \Carbon_Fields\Field::make('html', 'signup_button_html')
                ->set_html('<button type="button" id="signupBtn" class="button button-primary" data-hbm-auth=\'{"mode":"test", "action":"signup" , "display": "new-window"}\'>Sign Up with '
                    . $framework_context->label . '</button>')
                ->set_help_text('Click this button to test signup workflow of the Entra configuration'),
            \Carbon_Fields\Field::make('html', 'login_button_html')
                ->set_html('<button type="button" id="loginBtn" class="button button-primary" data-hbm-auth=\'{"mode":"test", "action":"login" , "display": "new-window"}\'>Login with '
                    . $framework_context->label . '</button>')
                ->set_help_text('Click this button to test login workflow of the Entra configuration'),
            \Carbon_Fields\Field::make('checkbox', 'hbm-auth-insert-test-user-on-login', 'Insert test user on login') // Field to delete the fields on plugin uninstallation in the database
                ->set_help_text('If you want to insert a test user on login, check this option.'),
            \Carbon_Fields\Field::make('html', 'logout_button_html')
                ->set_html('<button type="button" id="logoutBtn" class="button button-primary" data-hbm-auth=\'{"mode":"test", "action":"logout" , "display": "new-window"}\'>Logout from ' . $framework_context->label . '</button>')
                ->set_help_text('Click this button to test logout workflow of the Entra configuration'),
        );
        return array_merge($fields, $spinner_fields);
    }

    public function clear_transient_callback()
    {
        try {
            global $wpdb;

            // SQL query to select all option names that start with 'hbm_'
            $query = "SELECT option_name FROM $wpdb->options WHERE option_name LIKE '_transient_hbm-auth-%'
                    OR option_name LIKE '_transient_timeout_hbm-auth-%'";

            // Get the results
            $results = $wpdb->get_col($query);

            hbm_log("Deleting all options that start with 'hbm_'" . PHP_EOL . "Results: " . print_r($results, true));
            // Loop through each result and delete the option
            foreach ($results as $option_name) {
                delete_option($option_name);
            }

            wp_send_json_success();
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }

}