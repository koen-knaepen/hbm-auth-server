<?php

include_once HBM_PLUGIN_PATH . 'admin/options/class-options-main.php'; // main options
include_once HBM_PLUGIN_PATH . 'admin/options/class-options-client-sites.php'; // Client sites options
require_once HBM_PLUGIN_PATH . 'admin/options/helper-options.php'; // Logging functionality

class HBM_Server_Auth_Admin_options
{

    private $main_options;
    private $framework;
    private $framework_option;
    private $framework_label;
    private $client_sites;
    private $admin_clipboard = array();
    /**
     * Summary of __construct
     * Start the admin panel functionality
     * Create a new instance of the class
     * @param void
     * @return void
     */
    public function __construct()
    {
        // add_filter('hbm_auth_get_admin_clipboard', array($this, 'get_admin_clipboard'));
        $this->framework_option = get_option('_hbm-auth-framework'); // Retrieve the framework from the main options;
        switch ($this->framework_option) {
            case 'cognito':
                include_once HBM_PLUGIN_PATH . 'admin/options/class-options-cognito.php'; // Configuration options for Cognito
                $this->framework = new HBM_Auth_Admin_Options_Cognito();
                $this->framework_label = 'Cognito Configuration';
                break;
            case 'entra':
                include_once HBM_PLUGIN_PATH . 'admin/options/class-options-entra.php'; // Configuration options for Entra
                $this->framework = new HBM_Auth_Admin_Options_Entra();
                $this->framework_label = 'Entra Configuration';
                break;
            default:
                trigger_error("There is no valid framework available", E_USER_WARNING);
                $this->framework = null;
                $this->framework_label = 'No valid framework available';
                break;
        }
        $this->main_options = new HBM_Auth_Admin_Options_Main();
        $this->client_sites = new HBM_Auth_Admin_Options_Client_Sites();
        add_action('carbon_fields_register_fields', array($this, 'register_auth_options')); // Register the authentication options in the admin panel
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_options_assets')); // Enqueue the admin assets
        add_action('admin_notices', array($this, 'hbm_display_admin_notices')); // Display the admin notices (errors)
    }

    public function enqueue_admin_options_assets()
    {
        // 2. Enqueue FontAwesome
        wp_enqueue_style('fontawesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css', array(), '5.15.3');

        wp_enqueue_style('custom-admin-styles', HBM_PLUGIN_URL . '/admin/css/admin.css');
        wp_enqueue_script('hbm-auth-admin-js', HBM_PLUGIN_URL . '/admin/js/admin-scripts.js', array(), '1.0.0', true);
        wp_enqueue_script('hbm-spinner', HBM_PLUGIN_URL . '/admin/js/spinner-config.js', array('jquery'), '1.0.0', true);
        wp_localize_script(
            'hbm-spinner',
            'spinnerConfig',
            array(
                'testFramework' => 'test_framework',
                'clearTransient' => 'clear_transient',
            )
        );

    }


    public function hbm_display_admin_notices()
    {
        settings_errors('hbm-auth');
    }

    function displayfields()
    {
        try {
            $display_fields = $this->framework->displayFields();
        } catch (Exception $e) {
            error_log("Error: " . $e->getMessage());
            $display_fields = array();
            add_settings_error('hbm-auth', 'hbm-auth-error', 'Error: There is no valid framework for authentication --> ' . $e->getMessage(), 'error');
        } catch (Error $e) {
            error_log("Error: " . $e->getMessage());
            $display_fields = array();
            add_settings_error('hbm-auth', 'hbm-auth-error', 'Error: There is no valid framework for authentication --> ' . $e->getMessage(), 'error');
        } finally {
            return $display_fields;
        }
    }

    public function add_to_admin_clipboard($value)
    {
        $this->admin_clipboard[] = $value;
    }

    public function register_auth_options()
    {


        \Carbon_Fields\Container::make('theme_options', 'HBM Auth Server')
            ->set_classes('hbm-admin')
            ->add_tab(
                ('Main Options'), $this->main_options->displayFields()
            )
            ->add_tab(
                ("{$this->framework_label}"), $this->displayFields()
            )
            ->add_tab(
                ("Client Sites"), $this->client_sites->displayFields()
            );

        ;
    }

}