<?php
// class-km2c-shortcodes.php

class HBM_Shortcodes
{

    private $active_page;
    public function __construct()
    {
        // Register the shortcode when the class is instantiated.
        add_shortcode('hbm_login_button', array($this, 'render_login_button'));
        add_shortcode('hbm_site_url', array($this, 'shortcode_site_url'));
    }


    public function render_login_button($attributes)
    {

        // Set default attributes
        $attributes = shortcode_atts(
            array(
                'display_login' => 'new-window',
                'display_logout' => 'new-window',
            ),
            $attributes,
            'hbm_login_button'
        );


        // Retrieve options from Carbon Fields (if you've set them up).

        $button_login_text = carbon_get_theme_option('hbm-auth-login-button-text');
        $button_logout_text = carbon_get_theme_option('hbm-auth-logout-button-text');
        // If you haven't set up Carbon Fields for this, just define a default text:
        if ($button_login_text == '') {
            $button_login_text = 'Login';
        }
        if ($button_logout_text == '') {
            $button_logout_text = 'Logout';
        }
        $is_user = is_user_logged_in();

        $display_logout = $attributes['display_logout'];
        $display_login = $attributes['display_login'];
        if ($is_user) {
            $current_user = wp_get_current_user();
            // echo 'Welcome, ' . $current_user->display_name . '!'; // Display the user's display name
            // echo 'Your username is: ' . $current_user->user_login; // Display the user's username
            // echo 'Your email is: ' . $current_user->user_email; // Display the user's email
            return
                '<button type="button" class="button button-primary hbm-auth-logout" '
                . " data-hbm-auth='{\"mode\":\"live\", \"action\":\"logout\" , \"display_login\": \"{$display_login}\" , \"display_logout\" : \"{$display_logout}\" }'>"
                . esc_html($button_logout_text) . " " . esc_html($current_user->display_name) . '</button>';
        } else {
            return
                "<button type='button' class='button button-primary hbm-auth-login'"
                . " data-hbm-auth='{\"mode\":\"live\", \"action\":\"login\" , \"display_login\" : \"{$display_login}\" , \"display_logout\" : \"{$display_logout}\"}'>"
                . esc_html($button_login_text) . '</button>';
        }
    }

    function shortcode_site_url()
    {
        return get_site_url();
    }
}
