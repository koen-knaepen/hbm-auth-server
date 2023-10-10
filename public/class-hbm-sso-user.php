<?php

namespace HBM\auth_server;

use Aws\CognitoIdentityProvider\CognitoIdentityProviderClient;
use Aws\Credentials\Credentials;

require_once HBM_MAIN_UTIL_PATH . 'pods-act.php';


/**
 * HBM_SSO_User Class
 *
 * This class is responsible for managing Single Sign-On (SSO) user data.
 * It transparently handles the storage and retrieval of user data from the PHP session
 * and provides hooks for other parts of the application to interact with the user data.
 */
class HBM_SSO_User
{
    /**
     * Stores SSO user data.
     *
     * @var array
     */
    private $sso_user = array();

    /**
     * Constructor.
     *
     * Initializes the SSO user with a default role of "guest".
     * Retrieves any existing SSO user data from the session.
     * Sets up hooks for getting and setting the user data.
     */
    public function __construct()
    {
        // Ensure the session is started.
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        // Set default user role.
        $this->sso_user['role'] = "guest";

        // Retrieve user data from the session, if available.
        $this->retrieve_from_session();

        // Set up hooks.
        add_filter('hbm_get_sso_user', array($this, 'get_sso_user'), 10, 1);
        add_action('hbm_set_sso_user', array($this, 'set_sso_user'), 10, 1);
        add_action('hbm_logout_sso_user', array($this, 'logout_sso_user'), 10, 0);
    }

    /**
     * Retrieves the SSO user data.
     *
     * @param mixed $default_value Default value to return if no user data is set.
     * @return array
     */
    public function get_sso_user($default_value = null)
    {
        $settings = \hbm_fetch_pods_act('hbm-auth-server');
        error_log('$settings: ' . print_r($settings, true));

        $credentials = new Credentials('<Your-AWS-Access-Key>', '<Your-AWS-Secret-Key>');

        $client = new CognitoIdentityProviderClient([
            'region' => '<Your-Region>',
            'version' => 'latest',
            'credentials' => $credentials
        ]);

        // Assuming you have saved the JWT token in a session or cookie after login
        $accessToken = $_SESSION['cognito_access_token']; // or retrieve from cookie

        try {
            $result = $client->getUser([
                'AccessToken' => $accessToken
            ]);

            return $result['Username'];
        } catch (\Exception $e) {
            // Handle the error (e.g., user not authenticated, token expired, etc.)
            return null;
        }
    }

    /**
     * Sets the SSO user data.
     *
     * Merges the provided user data with the existing data and stores it in the session.
     *
     * @param array $sso_user SSO user data to set.
     */
    public function set_sso_user($sso_user)
    {
        $this->sso_user = array_merge($this->sso_user, $sso_user);
        $this->store_to_session();
    }

    public function logout_sso_user()
    {
        $this->sso_user = array(
            'role' => 'guest'
        );
        $this->store_to_session();
    }

    /**
     * Stores the SSO user data to the session.
     *
     * This method is automatically called when setting user data to ensure
     * the session always reflects the current state of the user data.
     */
    private function store_to_session()
    {
        $_SESSION['hbm_sso_user'] = $this->sso_user;
    }

    /**
     * Retrieves the SSO user data from the session.
     *
     * This method is automatically called during class instantiation to
     * populate the user data from any existing session data.
     */
    private function retrieve_from_session()
    {
        if (isset($_SESSION['hbm_sso_user'])) {
            $this->sso_user = $_SESSION['hbm_sso_user'];
        }
    }
}
