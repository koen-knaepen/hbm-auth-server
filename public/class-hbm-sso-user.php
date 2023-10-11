<?php

namespace HBM\auth_server;

require_once HBM_MAIN_UTIL_PATH . 'redis.php';

class HBM_SSO_User_Session
{

    private $sso_user_session = null;
    private $session_id = null;
    private $application = null;

    static protected $instances = null;

    public function __construct($application)
    {
        $this->application = $application;
        $this->session_id = $this->get_session_id();
        if (!$this->session_id) {
            $this->sso_user_session = array(
                'logged_in' => false,
                'role' => 'guest'
            );
        } else {
            $this->sso_user_session = \hbm_get_session($this->session_id);
        }
    }

    static function get_instance($application)
    {
        if (!isset(self::$instances[$application])) {
            self::$instances[$application] = new HBM_SSO_User_Session($application);
        }
        return self::$instances[$application];
    }

    public function init($logged_in, $role)
    {
        $this->sso_user_session = array(
            'logged_in' => $logged_in,
            'role' => $role
        );
        $this->session_id =  $this->set_session_id();
        \hbm_add_to_session($this->session_id, $this->sso_user_session);
    }

    public function set_sso_user($user)
    {
        if (!$this->session_id) {
            $this->init(true, 'subscriber');
        }
        $this->sso_user_session = \hbm_add_to_session($this->session_id, $user);
    }
    public function get_sso_logout()
    {
        return isset($this->sso_user_session['sso_logout']) ? $this->sso_user_session['sso_logout'] : false;
    }

    public function set_sso_logout($state)
    {
        if (!$this->session_id) {
            $this->init(true, 'subscriber');
        }
        $this->sso_user_session = \hbm_add_to_session($this->session_id, array('sso_logout' => $state));
    }

    public function logout_sso_user()
    {
        $this->sso_user_session = array(
            'logged_in' => false,
            'role' => 'guest'
        );
        \hbm_remove_session($this->session_id);
        $this->remove_session_id();
    }

    private function get_session_id()
    {
        $cookie = false;
        if (isset($_COOKIE["hbm_sso_user-{$this->application}"])) {
            $cookie = \wp_unslash($_COOKIE["hbm_sso_user-{$this->application}"]);
        }
        return $cookie;
    }

    private function set_session_id($ttl = 3600)
    {
        $session_id = uniqid();
        setcookie("hbm_sso_user-{$this->application}",  $session_id, time() + $ttl, '/', '', false, true);
        return $session_id;
    }

    private function remove_session_id()
    {
        setcookie("hbm_sso_user-{$this->application}", '', time() - 3600, '/', '', false, true);
    }
}

function hbm_set_logout($application, $state)
{
    $instance =  HBM_SSO_User_Session::get_instance($application);
    $instance->set_sso_logout($state);
}

function hbm_get_logout($application)
{

    $instance =  HBM_SSO_User_Session::get_instance($application);
    return $instance->get_sso_logout();
}

function hbm_logout($application)
{
    $instance =  HBM_SSO_User_Session::get_instance($application);
    $instance->logout_sso_user();
}

function hbm_set_sso_user($application, $user)
{
    $instance =  HBM_SSO_User_Session::get_instance($application);
    $instance->set_sso_user($user);
}
