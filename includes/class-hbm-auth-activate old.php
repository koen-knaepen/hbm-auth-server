<?php

namespace HBM\auth_server;

// Include the necessary WordPress plugin functions

include_once ABSPATH . 'wp-admin/includes/plugin.php';
include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
include_once ABSPATH . 'wp-admin/includes/class-plugin-upgrader.php';
include_once ABSPATH . 'wp-admin/includes/class-plugin-installer-skin.php';
include_once ABSPATH . 'wp-admin/includes/plugin-install.php';
include_once ABSPATH . 'wp-admin/includes/file.php';
include_once ABSPATH . 'wp-admin/includes/plugin.php';

/* 
* This class is used to install and activate the PODS plugin
* when the HBM Auth plugin is activated.
* @since 1.0.0
* @package HBM_Auth
* @class HBM_Auth_Activate
*/

class HBM_Auth_Activate
{
    private $main_activation;
    public function __construct($main_activation)
    {
        $this->main_activation = $main_activation;
        if (!$this->main_activation) {
            error_log('Constants of the plugin' . plugin_dir_path(__FILE__));
            add_action('activated_plugin', array($this, 'deactivate_again'));
            add_action('admin_init', array($this, 'hbm_check_for_admin_notices'));
        } else {
            add_action('activated_plugin', array($this, 'hbm_on_plugin_activation'), 10, 1);
            add_action('admin_init', array($this, 'hbm_auth_server_check_pods_import_activation'));
            add_action('admin_init', array($this, 'hbm_auth_server_intialize_pods_packages'));
        }
    }

    /***************************  THIS PART IS TO HANDLE PREMATURE ACTIVATION ***************/
    function register_special_notice_styles()
    {
        wp_enqueue_style('custom-notice-styles', HBM_PLUGIN_URL . '/admin/css/remove-system-notice.css');
    }

    function deactivate_again()
    {
        set_transient('hbm_show_admin_notice', true, 60);
    }

    function hbm_display_admin_notices()
    {
        add_settings_error('hbm-main', 'hbm-auth', 'The HBM Main plugin is not active. Please activate it first.', 'warning');
        settings_errors('hbm-main');
        deactivate_plugins(HBM_PLUGIN_BASENAME);
    }

    function hbm_check_for_admin_notices()
    {
        // Check if the transient exists
        if (get_transient('hbm_show_admin_notice')) {
            add_action('admin_enqueue_scripts', array($this, 'register_special_notice_styles'));
            add_action('admin_notices', array($this, 'hbm_display_admin_notices'));
            // Delete the transient
            delete_transient('hbm_show_admin_notice');
        }
    }

    /***************************  THIS PART IS TO HANDLE ACTIVATION AND INSTALL OF PODS PACKAGES ***************/
    function hbm_on_plugin_activation($plugin)
    {
        if ($plugin == HBM_PLUGIN_BASENAME) {
            $pods_components_class = pods_components();
            $migrate_active = $pods_components_class->is_component_active('migrate-packages');
            if ($migrate_active) {
                set_transient('hbm_pods_import_package', 'enabled', 60);
            } else {
                set_transient('hbm_pods_migrate_activation', true, 60);
            }
        }
    }
    function hbm_auth_server_check_pods_import_activation()
    {
        if (get_transient('hbm_pods_migrate_activation')) {
            $installed_components = array();
            $pods_components_class = pods_components();
            $pods_components = $pods_components_class->get_components();
            foreach ($pods_components as $component) {
                if ($pods_components_class->is_component_active($component['ID'])) {
                    $installed_components[] = $component['ID'];
                }
            }
            $pods_components_class->activate_component('migrate-packages');
            set_transient('hbm_pods_import_package', $installed_components, 60);
            delete_transient('hbm_pods_migrate_activation');
            wp_redirect($_SERVER['REQUEST_URI']);
            exit;
        }
    }


    function hbm_auth_server_intialize_pods_packages()
    {
        $installed_components = get_transient('hbm_pods_import_package');
        if ($installed_components !== false) {
            $pods_slug = $this->get_latest_hbm_package(HBM_PLUGIN_PATH . 'pods-packages', 'hbm-auth-server-pods-package');
            $pods_file = HBM_PLUGIN_PATH . 'pods-packages/' . $pods_slug;
            $json_data = file_get_contents($pods_file);
            $pods_upload = false;
            if (!pods('hbm-auth-server')) {
                if ($pods_slug === false) {
                    delete_transient('hbm_pods_import_package');
                    throw new Exception('No PODS package found.');
                } else {
                    $pods_upload = true;
                }
            }
            if (pods('hbm-auth-server') && $pods_slug !== false) {
                $auth_pods_update = pods('hbm-auth-server')->field('hbm_auth_server_automatic_updates');
                if ($auth_pods_update) {
                    $auth_pods_file = pods('hbm-auth-server')->field('hbm_auth_server_initial_pods_package');
                    $auth_pods_checksum = pods('hbm-auth-server')->field('hbm_auth_server_pods_package_checksum');
                    $pods_checksum = hash_file('sha256', $pods_file);
                    if ($auth_pods_file !== $pods_slug || $auth_pods_checksum !== $pods_checksum) {
                        $pods_upload = true;
                    }
                }
            }
            if ($pods_upload) {
                try {
                    $pods_api = pods_api();
                    pods('hbm-auth-server')->save(array(
                        'hbm_auth_server_initial_pods_package' => $pods_slug,
                        'hbm_auth_server_pods_package_checksum' => $pods_checksum
                    ), null, true);
                    $package_result = $pods_api->import_package($json_data, false);
                } catch (Exception $e) {
                    error_log('Error importing PODS package: ' . print_r($e->getMessage(), true));
                } catch (Error $e) {
                    error_log('Error importing PODS package: ' . print_r($e->getMessage(), true));
                }
            }
            if ($installed_components != 'enabled') {
                $pods_components_class = pods_components();
                $current_components = $pods_components_class->get_components();
                foreach ($current_components as $component) {
                    if (in_array($component['ID'], $installed_components)) {
                        $pods_components_class->activate_component($component['ID']);
                    } else {
                        $pods_components_class->deactivate_component($component['ID']);
                    }
                }
            }
            delete_transient('hbm_pods_import_package');
            wp_redirect($_SERVER['REQUEST_URI']);
            exit;
        }
    }

    function get_latest_hbm_package($dir_path, $pattern)
    {
        $files = scandir($dir_path);

        // Filter files based on the pattern
        $filtered_files = array_filter($files, function ($file) use ($pattern) {  // Note the "use" keyword here
            $regex_match = '/^' . $pattern . '-\d{4}-\d{2}-\d{2}\.json$/';
            $found_file =  preg_match($regex_match, $file);
            return $found_file;
        });

        // If no files found, return null
        if (empty($filtered_files)) {
            return false;
        }

        // Sort the files
        sort($filtered_files);

        // Get the latest file
        $latest_file = end($filtered_files);

        // Return the full path of the latest file
        return  $latest_file;
    }
}
