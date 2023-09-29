<?php

namespace HBM\auth_server;

class HBM_User_Meta_Handler
{

    public function __construct()
    {
        // Add Carbon Fields user meta field
        add_action('carbon_fields_register_fields', array($this, 'register_user_fields'));

        // Save user meta upon registration
        // add_action('user_register', array($this, 'save_user_meta_on_registration'));

        // Save user meta upon login
        // add_action('wp_login', array($this, 'save_user_meta_on_login'), 10, 2);


    }

    /**
     * Register the hbm_entra_id user meta field using Carbon Fields.
     */
    public function register_user_fields()
    {

        // Check if there's a user_id parameter in the request
        if (isset($_REQUEST['user_id'])) {
            $user_id = intval($_REQUEST['user_id']);
        } else {
            $user_id = get_current_user_id();
        }
        $framework_context = apply_filters('hbm_get_framework_context', null);

        $hbm_framework_id = get_user_meta($user_id, $framework_context->metadata, true); // Retrieve the user meta value for the specified user

        \Carbon_Fields\Container::make('user_meta', 'Extra Profile Information')
            ->set_classes('hbm-framework-id-display-container')
            ->add_fields(
                array(
                    \Carbon_Fields\Field::make('text', $framework_context->metadata, "Framework ({$framework_context->label}) ID")
                        ->set_attribute('readOnly', true)
                        ->set_classes('hbm-input-readonly')
                )
            );
    }

    /**
     * Save the hbm_entra_id user meta field upon user registration.
     */
    public function save_user_meta_on_registration($user_id)
    {
        if (isset($_POST['hbm_entra_id'])) {
            carbon_set_user_meta($user_id, 'hbm_entra_id', sanitize_text_field($_POST['hbm_entra_id']));
        }
    }

    /**
     * Save the hbm_entra_id user meta field upon user login.
     */
    public function save_user_meta_on_login($user_login, $user)
    {
        $hbm_entra_id_value = 'some_value'; // Replace with the actual value you want to save
        update_user_meta($user->ID, 'hbm_entra_id', $hbm_entra_id_value);
    }
}
