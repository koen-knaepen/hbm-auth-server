<?php
// class-km2c-shortcodes.php

class HBM_Server_Auth_Admin_Shortcodes
{

    public function __construct()
    {
        // Register the shortcode when the class is instantiated.
        add_shortcode('hbm_site_url', array($this, 'shortcode_site_url'));
        add_shortcode('hbm_callback_url', array($this, 'shortcode_callback'));
        add_shortcode('hbm_copy_wrapper', array($this, 'shortcode_copy_clipboard_wrapper'));
    }

    function shortcode_site_url()
    {
        return get_site_url();
    }

    function shortcode_callback($attributes)
    {
        $specific_domain = pods('hbm-auth-server')->field('hbm_auth_server_test_server');
        if ($specific_domain) {
            $domain = pods('hbm-auth-server')->field('hbm_auth_server_test_domain');
        } else {
            $domain = get_site_url();
        }
        $attributes = shortcode_atts(
            array(
                'slug' => 'callback',
            ),
            $attributes,
            'hbm_callback_url'
        );
        switch ($attributes['slug']) {
            case 'callback':
                $slug = '/wp-json/hbm-auth/callback';
                break;
            case 'logout':
                $slug = '/wp-json/hbm-auth/framework_logout';
                break;
            case 'sso':
                $slug = '/wp-json/hbm-auth/sso_status';
                break;
            default:
                $slug = '/wp-json/hbm-auth/callback';
                break;
        }
        return $domain . $slug;
    }

    function shortcode_copy_clipboard_wrapper($attributes = null, $content = null)
    {
        return '<div class="hbm-clipboard"><span >' .
            do_shortcode($content) .
            '</span> <button type="button" class="hbm-copy-clipboard"><i class="fas fa-copy"></i></button></div>';
    }
}
