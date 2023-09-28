<?php
include_once HBM_AUTH_SERVER_PATH . 'admin/cleanup-db.php';

class HBM_Auth_Admin_Options_Client_Sites
{
    public function __construct()
    {
        // Register the deactivation hook using the defined constant
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_main_assets')); // Enqueue the admin assets
    }

    public function enqueue_admin_main_assets()
    {

        // 2. Enqueue the script to ask for confirmation when deactivating the plugin
        wp_enqueue_script('hbm-deactivate-script', HBM_AUTH_SERVER_URL . '/admin/js/deactivate-script.js', array('jquery'), '1.0.0', true);

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
        $site_labels = array(
            'plural_name' => 'Client Sites',
            'singular_name' => 'Client Site',
        ); // Labels for the client sites

        $fields = array(
            \Carbon_Fields\Field::make('complex', 'hbm-auth-sites', 'Client Sites') // Field to select the authentication mode
                ->setup_labels($site_labels)
                ->set_duplicate_groups_allowed(false)
                ->set_collapsed(true)
                ->set_min(1)
                ->add_fields(
                    array(
                        \Carbon_Fields\Field::make('text', 'domain', 'Client Site Domain') // Client ID
                            ->set_required(true)
                            ->set_help_text('Enter the domain of the client site.'),
                        \Carbon_Fields\Field::make('text', 'secret', 'Client Site Secret') // Client ID
                            ->set_help_text('Here is the secret key for the client site.')
                            ->set_attribute('readOnly', true)
                            ->set_classes('hbm-input-readonly')
                            ->set_attribute('type', 'password'),
                    )
                )
                ->set_header_template(' Site: <%- domain %>'),
            \Carbon_Fields\Field::make('text', 'hbm-auth-sso-server-url', 'Auth and SSO Server Domain') // Client ID
                ->set_help_text('Enter the full url of the Wordpress that is used for Authentication and Single Sign On.')
                ->set_attribute('placeholder', 'https://myssosite.com'),

        );
        return $fields;
    }
}
