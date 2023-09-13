<?php
include_once HBM_PLUGIN_PATH . 'admin/cleanup-db.php';

class HBM_Auth_Admin_Options_Main
{
    public function __construct()
    {
        // Register the deactivation hook using the defined constant
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_main_assets')); // Enqueue the admin assets
    }

    public function enqueue_admin_main_assets()
    {

        // 2. Enqueue the script to ask for confirmation when deactivating the plugin
        wp_enqueue_script('hbm-deactivate-script', HBM_PLUGIN_URL . '/admin/js/deactivate-script.js', array('jquery'), '1.0.0', true);

        // 3. Pass the value to the script for the deactivate script
        $delete_option = carbon_get_theme_option('hbm-auth-delete-fields-on-deactivate');
        wp_localize_script(
            'hbm-deactivate-script',
            'hbmData',
            array(
                'shouldConfirm' => $delete_option
            )
        );


    }


    public function displayFields()
    {
        $sso_server = get_option('_hbm-auth-sso-server-url');
        if ($sso_server == '') {
            $redirect_url = site_url('/wp-json/hbm-auth/callback'); // Construct the redirect URL based on the site's domain
            $logout_url = site_url('/wp-json/hbm-auth/framework_logout'); // Construct the logout URL based on the site's domain
            $sso_url = site_url('/wp-json/hbm-auth/sso_status'); // Construct the redirect URL based on the site's domain
        } else {
            $redirect_url = $sso_server . '/wp-json/hbm-auth/callback'; // Construct the redirect URL based on the domain of the SSO server
            $logout_url = $sso_server . '/wp-json/hbm-auth/framework_logout'; // Construct the logout URL based on the domain of the SSO server
            $sso_url = $sso_server . '/wp-json/hbm-auth/sso_status'; // Construct the redirect URL based on the site's domain
        }
        $framework_context = apply_filters('hbm_get_framework_context', '');

        $fields = array(
            \Carbon_Fields\Field::make('radio', 'hbm-auth-framework', 'Framework to use for authentication') // Field to select the authentication mode
                ->add_options(
                    array(
                        'cognito' => 'AWS Cognito',
                        'entra' => 'Microsoft Entra',
                        'google' => 'Google Identity'
                    )
                )
                ->set_default_value('entra')
                ->set_classes('hbm-auth-horizontal-radio')
                ->set_help_text('Select the authentication mode. If you select WP Auth only, the Entra Auth will be disabled. If you select Entra Auth only, the WP Auth will be disabled. If you select Both WP Auth and Entra Auth, both will be enabled.'),
            \Carbon_Fields\Field::make('checkbox', 'hbm-auth-delete-fields-on-deactivate', 'Delete fields on plugin deactivation?') // Field to delete the fields on plugin deactivation in the database
                ->set_help_text('If you want to keep the fields in the database after deactivation of the plugin, uncheck this option.'),
            \Carbon_Fields\Field::make('checkbox', 'hbm-auth-delete-fields-on-uninstall', 'Delete fields on plugin uninstallation?') // Field to delete the fields on plugin uninstallation in the database
                ->set_help_text('If you want to keep the fields in the database after uninstallation of the plugin, uncheck this option.')
                ->set_default_value('yes'),
            \Carbon_Fields\Field::make('html', 'hbm-auth-redirect-url-display', 'Redirect URL') // Field to display the redirect URL
                ->set_help_text("Copy this URL and paste it in the Redirect URL field in the application in {$framework_context->label} you want to use")
                ->set_html('<div class="hbm-clipboard"><strong>Redirect URL:</strong> <span >' .
                    esc_html($redirect_url) .
                    '</span> <button type="button" class="hbm-copy-clipboard"><i class="fas fa-copy"></i></button></div>'), // Create a button to copy the redirect URL to the clipboard
            \Carbon_Fields\Field::make('html', 'hbm-auth-logout-url-display', 'Logout URL') // Field to display the redirect URL
                ->set_help_text("Copy this URL and paste it in the Logout URL field in the application in {$framework_context->label} you want to use")
                ->set_html('<div class="hbm-clipboard"><strong>Logout URL:</strong> <span >' .
                    esc_html($logout_url) .
                    '</span> <button type="button" class="hbm-copy-clipboard"><i class="fas fa-copy"></i></button></div>'), // Create a button to copy the redirect URL to the clipboard
            \Carbon_Fields\Field::make('html', 'hbm-auth-redirect-sso-url-display', 'SSO URL') // Field to display the redirect URL
                ->set_help_text("Copy this URL and paste it in the Redirect URL field in the application in {$framework_context->label} you want to use")
                ->set_html('<div class="hbm-clipboard"><strong>SSO Server URL:</strong> <span >' .
                    esc_html($sso_url) .
                    '</span> <button type="button" class="hbm-copy-clipboard"><i class="fas fa-copy"></i></button></div>'), // Create a button to copy the redirect URL to the clipboard

        );
        return $fields;
    }

}