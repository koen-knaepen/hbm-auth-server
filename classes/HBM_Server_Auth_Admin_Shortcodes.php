<?php

namespace HBM\auth_server;

use HBM\Instantiations\HBM_Class_Handler;

class HBM_Server_Auth_Admin_Shortcodes extends HBM_Class_Handler
{

    public function __construct()
    {
        // Register the shortcode when the class is instantiated.
        add_shortcode('hbm_callback_url', array($this, 'shortcode_callback'));
    }

    protected static function set_pattern(): array
    {
        return [
            'pattern' => 'singleton',
            '__log' => ['name'],
            '__ticket' =>
            ['Entry' => ['is_admin']],
        ];
    }

    function shortcode_callback($attributes)
    {
        $specific_domain = pods('hbm-auth-server')->field('test_server');
        if ($specific_domain) {
            $domain = pods('hbm-auth-server')->field('test_domain');
        } else {
            $domain = get_site_url() . '/';
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
                $slug = 'wp-json/hbm-auth-server/callback';
                break;
            case 'logout':
                $slug = 'wp-json/hbm-auth-server/framework_logout';
                break;
            case 'logout-long':
                $slug = 'wp-json/hbm-auth-server/framework_logout?app=';
                break;
            case 'sso':
                $slug = 'wp-json/hbm-auth-server/sso_status';
                break;
            default:
                $slug = 'wp-json/hbm-auth-server/callback';
                break;
        }
        return  $domain . $slug;
    }
}
