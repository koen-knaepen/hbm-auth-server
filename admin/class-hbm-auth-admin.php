<?php

require_once HBM_PLUGIN_PATH . 'admin/options/class-options.php'; // Admin-related functionality
require_once HBM_PLUGIN_PATH . 'admin/users/class-hbm-user-meta-handler.php'; // User-related functionality


/**
 * Class HBM_Auth_Admin
 * This class is responsible for the admin panel functionality
 * @package HBM_Entra_Auth
 * @subpackage admin
 */

class HBM_Auth_Admin
{
    private $admin_options;
    private $user_meta_handler;
    public $options_updated = false;

    /**
     * Summary of __construct
     * Start the admin panel functionality
     * Create a new instance of the class
     * @return void
     */
    public function __construct()
    {
        if (is_admin()) {
            $this->user_meta_handler = new HBM_User_Meta_Handler();
            $this->admin_options = new HBM_Auth_Admin_Options();
            settings_errors('hbm-auth', false, true); // Display the error message in the admin panel

        }
    }

    // public function hbm_enqueue_admin_assets()
    // {    }

    public function hbm_plugin_deactivate()
    {
        if (!current_user_can('activate_plugins')) {
            return;
        }

        error_log('Deactivating hbm-entra-auth plugin...');

        $delete_option = carbon_get_theme_option('hbm-auth-delete-fields-on-deactivate');

        if ($delete_option) {
            hbm_log("Deactivating hbm-entra-auth plugin..." . PHP_EOL . "Delete fields on deactivation: " . $delete_option);
            hbm_cleanup_database();
        }

        remove_action('hbm_after_cpt_registration', 'flush_rewrite_rules');

        flush_rewrite_rules();

    } // Other admin-related methods can be added here

    public function hbm_plugin_activate()
    {
        if (!current_user_can('activate_plugins')) {
            return;
        }

        error_log('Activating hbm-entra-auth plugin...');

        // HBM_Auth_Redirect_Pages::register_custom_post_type();
        add_action('hbm_after_cpt_registration', 'flush_rewrite_rules');

    } // Other admin-related methods can be added here


}