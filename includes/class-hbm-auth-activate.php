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
        error_log('Feedback: ' . $string);
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

    public function hbm_user_capability()
    {
        if (!current_user_can('activate_plugins')) {
            exit('You do not have permission to activate plugins.');
        }
    }
}
