<?php

namespace HBM\auth_server;

use function HBM\hbm_add_to_session;

require_once HBM_MAIN_UTIL_PATH . 'redis.php';

class HBM_SSO_User
{

    private $sso_user_session = null;
    private $session_id = null;

    private $instance = null;

    public function __construct()
    {

        $this->session_id = $this->get_session_id();
        if (!$this->session_id) {
            $this->sso_user_session = array(
                'logged_in' => false,
                'role' => 'guest'
            );
        } else {
            $this->sso_user_session = hbm_get_session($this->session_id);
        }
    }

    static function get_instance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new HBM_SSO_User();
        }
        return self::$instance;
    }

    public function init($logged_in, $role)
    {
        $this->sso_user_session = array(
            'logged_in' => $logged_in,
            'role' => $role
        );
        $this->session_id =  $this->set_session_id();
        hbm_add_to_session($this->session_id, $this->sso_user_session);
    }

    public function get_sso_logout()
    {
        return isset($this->sso_user_session['sso_logout']) ? $this->sso_user_session['sso_logout'] : false;
    }

    public function set_sso_logout($state)
    {
        error_log('set_sso_user' . print_r($state, true));
        if (!$this->session_id) {
            $this->init(true, 'subscriber');
        }
        $this->sso_user_session = hbm_add_to_session($this->session_id, array('sso_logout' => $state));
    }

    public function logout_sso_user()
    {
        $this->sso_user_session = array(
            'logged_in' => false,
            'role' => 'guest'
        );
        hbm_remove_session($this->session_id);
        $this->remove_session_id();
    }

    private function store_to_session()
    {
        $this->redis->set('hbm_sso_user', $this->sso_user_session);
    }

    private function retrieve_from_session()
    {
        $storedData = $this->redis->get('hbm_sso_user');
        if ($storedData) {
            $this->sso_user_session = $storedData;
        }
    }

    private function get_session_id()
    {
        $cookie = false;
        if (isset($_COOKIE['hbm_sso_user'])) {
            $cookie = \wp_unslash($_COOKIE['hbm_sso_user']);
        }
        return $cookie;
    }

    private function set_session_id($ttl = 3600)
    {
        $session_id = uniqid();
        setcookie('hbm_sso_user', $session_id, time() + $ttl, '/', '', false, true);
        return $session_id;
    }

    private function remove_session_id()
    {
        setcookie('hbm_sso_user', '', time() - 3600, '/', '', false, true);
    }
}
