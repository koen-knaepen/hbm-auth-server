<?php

require_once HBM_PLUGIN_PATH . 'admin/options/class-options.php'; // Admin-related functionality
require_once HBM_PLUGIN_PATH . 'admin/users/class-hbm-user-meta-handler.php'; // User-related functionality


/**
 * Class HBM_Auth_Admin
 * This class is responsible for the admin panel functionality
 * @package HBM_Entra_Auth
 * @subpackage admin
 */

class HBM_Server_Auth_Admin
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
            $this->admin_options = new HBM_Server_Auth_Admin_Options();
            settings_errors('hbm-auth', false, true); // Display the error message in the admin panel

        }
    }
}
