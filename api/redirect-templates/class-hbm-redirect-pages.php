<?php
class HBM_Auth_Redirect_Pages
{

    public function __construct($file)
    {
        add_action('init', array($this, 'add_custom_endpoint'));
        add_action('template_redirect', array($this, 'azure_redirect_template'));
    }

    public function add_custom_endpoint()
    {
        add_rewrite_rule('^azure-redirect/?$', 'index.php?azure_redirect=true', 'top');
        add_rewrite_tag('%azure_redirect%', '([^&]+)');
        do_action('hbm_after_cpt_registration');
    }

    public function azure_redirect_template()
    {
        global $wp_query;

        if (isset($wp_query->query_vars['azure_redirect'])) {
            include HBM_PLUGIN_PATH . 'api/redirect-templates/azure-redirect-template.php';
            exit;
        }
    }
}