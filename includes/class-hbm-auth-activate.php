<?php

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
class Silent_Installer_Skin extends Plugin_Installer_Skin
{

    public function header()
    {
        // echo "Starting the plugin installation process...";
        // echo "The plugin installation process may take a few minutes...";
        // echo "We'll install also the free plugin PODS, which is required for this plugin to work.";
        // echo "Make sure you are connected to the internet.";
    }
    public function footer()
    {
    }
    public function feedback($string, ...$args)
    {
    }
    public function error($errors)
    {
        error_log('Error: ' . json_encode($errors, JSON_PRETTY_PRINT));
    }
}


class HBM_Auth_Activate
{
    public function __construct()
    {
        register_activation_hook(HBM_PLUGIN_FILE, array($this, 'hbm_plugin_activate'));
        add_action('activated_plugin', array($this, 'hbm_on_plugin_activation'), 10, 1);
        add_action('admin_init', array($this, 'hbm_check_pods_import_activation'));
        add_action('admin_init', array($this, 'hbm_intialize_pods_packages'));
    }

    public function hbm_plugin_activate()
    {
        if (!current_user_can('activate_plugins')) {
            return;
        }
        try {
            $plugin_path = 'pods/init.php';
            $all_plugins = get_plugins();

            if (isset($all_plugins[$plugin_path])) {
                if (!is_plugin_active($plugin_path)) {
                    $result = activate_plugin($plugin_path);
                    if (is_wp_error($result)) {
                        error_log('Error activating PODS plugin: ' . $result->get_error_message());
                        return;
                    }
                }
            } else {
                $install_result = $this->install_pods_plugin();
                if ($install_result === 'Plugin installed successfully.') {
                    $result = activate_plugin($plugin_path);
                    if (is_wp_error($result)) {
                        error_log('Error activating PODS plugin after installation: ' . $result->get_error_message());
                        return;
                    }
                } else {
                    error_log($install_result);
                    return;
                }
            }
        } catch (Exception $e) {
            error_log('Error activating PODS plugin: ' . $e->getMessage());
        } catch (Error $e) {
            error_log('Error activating PODS plugin: ' . $e->getMessage());
        }
    }

    function install_pods_plugin()
    {
        $plugin_zip_url = 'https://downloads.wordpress.org/plugin/pods.latest-stable.zip';
        $plugin_directory = WP_PLUGIN_DIR . '/pods';

        if (file_exists($plugin_directory)) {
            return 'Plugin already installed.';
        }

        try {
            $upgrader = new Plugin_Upgrader(new Silent_Installer_Skin());
            $installed = $upgrader->install($plugin_zip_url);
        } catch (Exception $e) {
            error_log('Error installing PODS plugin with Plugin_Upgrader: ' . $e->getMessage());
        } catch (Error $e) {
            error_log('Error installing PODS plugin with Plugin_Upgrader: ' . $e->getMessage());
        }
        if ($installed) {
            return 'Plugin installed successfully.';
        } else {
            return 'Plugin installation failed.';
        }
    }

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
    function hbm_check_pods_import_activation()
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


    function hbm_intialize_pods_packages()
    {
        $installed_components = get_transient('hbm_pods_import_package');
        if ($installed_components !== false) {
            $pods_slug = $this->get_latest_hbm_package(HBM_PLUGIN_PATH . 'pods-packages', 'hbm-auth-server-pods-package');
            if ($pods_slug === false) {
                delete_transient('hbm_pods_import_package');
                throw new Exception('No PODS package found.');
            }
            error_log('pods slug: ' . $pods_slug);
            $json_data = file_get_contents($pods_slug);
            $pods_api = pods_api();
            // if (!pods('hbm-auth')) {
            try {
                $package_result = $pods_api->import_package($json_data, false);
            } catch (Exception $e) {
                error_log('Error importing PODS package: ' . print_r($e->getMessage(), true));
            } catch (Error $e) {
                error_log('Error importing PODS package: ' . print_r($e->getMessage(), true));
            }
            // }
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
        return $dir_path . '/' . $latest_file;
    }
}
