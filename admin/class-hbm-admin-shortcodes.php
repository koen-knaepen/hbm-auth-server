<?php

namespace HBM\auth_server;

namespace HBM\auth_server;
// class-km2c-shortcodes.php

class HBM_Server_Auth_Admin_Shortcodes
{

    public function __construct()
    {
        // Register the shortcode when the class is instantiated.
        add_shortcode('hbm_callback_url', array($this, 'shortcode_callback'));
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
}
