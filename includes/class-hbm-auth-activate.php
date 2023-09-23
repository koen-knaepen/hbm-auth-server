<?php

// Include the necessary WordPress plugin functions

include_once ABSPATH . 'wp-admin/includes/plugin.php';
include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
include_once ABSPATH . 'wp-admin/includes/class-plugin-upgrader.php';
include_once ABSPATH . 'wp-admin/includes/class-plugin-installer-skin.php';
include_once ABSPATH . 'wp-admin/includes/plugin-install.php';
include_once ABSPATH . 'wp-admin/includes/file.php';
include_once ABSPATH . 'wp-admin/includes/plugin.php';

class HBM_Auth_Activate
{
    public function __construct()
    {
        // add_action('init', array($this, 'hbm_user_capability'));
        register_activation_hook(HBM_PLUGIN_FILE, array($this, 'hbm_plugin_activate'));
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
                error_log('Install result: ' . $install_result);
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
        $test = true;
        $plugin_zip_url = 'https://downloads.wordpress.org/plugin/pods.latest-stable.zip';
        $plugin_directory = WP_PLUGIN_DIR . '/pods';

        if (file_exists($plugin_directory)) {
            return 'Plugin already installed.';
        }

        try {
            $upgrader = new Plugin_Upgrader(new Plugin_installer_Skin());
            $installed = $upgrader->install($plugin_zip_url);
            error_log('Before activating HBM plugin ...');
            if ($test) return 'test';
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

    function download_and_install_pods_plugin()
    {
        $plugin_zip_url = 'https://downloads.wordpress.org/plugin/pods.latest-stable.zip';
        $tmp_file = download_url($plugin_zip_url);

        if (is_wp_error($tmp_file)) {
            error_log('Error downloading PODS plugin: ' . $tmp_file->get_error_message());
            return 'Plugin installation failed.';
        }

        $plugin_directory = WP_PLUGIN_DIR . '/pods';
        $result = unzip_file($tmp_file, $plugin_directory);

        if (is_wp_error($result)) {
            error_log('Error unzipping PODS plugin: ' . $result->get_error_message());
            return 'Plugin installation failed.';
        }

        return 'Plugin installed successfully.';
    }

    public function hbm_user_capability()
    {
        if (!current_user_can('activate_plugins')) {
            exit('You do not have permission to activate plugins.');
        }
    }
}
