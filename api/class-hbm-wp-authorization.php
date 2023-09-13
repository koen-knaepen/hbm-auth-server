<?php
class HBM_WP_Authorization
{
    private $secret_manager;

    // Constructor
    public function __construct($jwt_manager)
    {
        $this->secret_manager = $jwt_manager;
        add_action('rest_api_init', array($this, 'hbm_register_receive_token'));
    }

    public function enqueue_auth_authorize_script()
    {
    }

    public function hbm_register_receive_token()
    {
        // register route to validate the tokens
        register_rest_route(
            'hbm-auth/v1',
            'validate_token',
            array(
                'methods' => 'GET',
                'callback' => array($this, 'receive_token'),
                'permission_callback' => '__return_true'
            )
        );
        register_rest_route(
            'hbm-auth/v1',
            'logout-client',
            array(
                'methods' => 'GET',
                'callback' => array($this, 'handle_logout'),
                'permission_callback' => '__return_true'
            )
        );


    }
    public function receive_token(WP_REST_Request $request)
    {
        try {
            $state_urlcoded = $request->get_param('state');
            $state = urldecode($state_urlcoded);
            $state_payload = $this->secret_manager->decode_jwt($state);
            $auth_code_urlcoded = $request->get_param('access_code');
            $auth_code = urldecode($auth_code_urlcoded);
            $framework_user = extract_payload($auth_code);
            $framework_context = apply_filters('hbm_get_framework_context', '');
            // !!!!!!!!! Don't integrate the logout scenario here, there is a special function because the cycle is reversed !!!!!!!!!
            switch ($state_payload->action) {
                case 'signup':
                    $result = $this->handle_signup($state_payload, $framework_user);
                    break;
                case 'login':
                    $result = $this->handle_login($state_payload, $framework_user);
                    break;
                case 'profile_edit':
                    $result = $this->handle_profile_edit($state_payload, $framework_user);
                    break;
                default:
                    error_log('Unknown action: ' . $state_payload->action);
                    break;
            }
            $sso_user = $result['sso_user'];
            // @todo implement mechanism so the jwt can be checked on both sides with the client secret
            $sso_jwt_object = json_decode($this->secret_manager->encode_jwt((array) $sso_user, 'hbm-auth-session-', false));
            $sso_jwt = urlencode($sso_jwt_object->jwt);

            $sso_server = get_option('_hbm-auth-sso-server-url');
            $redirect_url = "{$sso_server}/wp-json/hbm-auth/v1/sso_status?state={$state_urlcoded}&access_code={$auth_code_urlcoded}&sso_user={$sso_jwt}";

            // If the mode is test, then display the result in a modal window and keep the status window open until the user closes it
            // Otherwise, redirect to the redirect_url
            if ($state_payload->mode == 'test') {
                $message = "<h3> You are on the server that made the request </h3>"
                    . "<p>Authentication from {framework} received: </p><pre>" . json_encode($state_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "</pre>"
                    . "<p>{$framework_context->label} user: </p><pre>" . json_encode($framework_user, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . '</pre>'
                    . "<p>Authorization Result:  </p><pre>" . json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "</pre>";
                echo_modal($redirect_url, $message);
            } else {
                hbm_set_headers();
                echo "<script type='text/javascript'>
                            window.opener.postMessage('childWindowClosed', '*');
                            window.location.href = '{$redirect_url}}';
                        </script>";
            }

        } catch (Exception $e) {
            // Handle the exception (e.g., log the error, return an error message, etc.)
            return 'error in the validation and authorization of the autheiticated user';
        }
    }

    /**
     * Handle user signup using OAuth2 data.
     *
     * @param array $user_data Data retrieved from OAuth2 provider.
     * @return String Message to display to the user
     */
    function handle_signup($state_payload, $framework_user)
    {
        // Implement signup logic here
        return 'Signup action received: ' . json_encode($state_payload) . '<br>' . 'Framework user: ' . json_encode($framework_user);
    }

    /**
     * Handle user login using OAuth2 data.
     *
     * @param array $user_data Data retrieved from OAuth2 provider.
     * @return string Message to display to the user
     */
    function handle_login($state_payload, $framework_user)
    {
        // Implement login logic here
        // Retrieve the scenario from the state payload

        $framework = apply_filters('hbm_get_framework_context', '');

        if ($state_payload->mode == 'test') {
            $create_user = get_option('_hbm-auth-insert-test-user-on-login');
        } else {
            // Check if the user exists in the database
            $create_user = get_option('_hbm-auth-insert-user-on-login');
        }
        $wp_user = apply_filters('hbm_get_wp_user_data', '', $framework_user);
        $user_created = false;

        // Check if the user exists in the database by the user key of the chosen framework
        $user = get_users(
            array(
                'meta_key' => $framework->metadata,
                'meta_value' => $wp_user->auth_id,
                'number' => 1,
                'count_total' => false
            )
        );

        // Check if the user exists in the database by the email returned by the chosen framework
        if (empty($user)) {
            $user = get_user_by('email', $wp_user->email);
            if (!empty($user))
                $user_created = true;
        }

        // Handle the user creation and update in function of the params available
        if (!empty($user)) {
            if ($user_created) {
                $user_id = $user->ID;
                $this->create_update_user($wp_user, $user_id);
            } else {
                $user_id = $user[0]->ID;
            }
        } else {
            if ($create_user) {
                // Create a new user with the UID and email
                $user_id = $this->create_update_user($wp_user, null);
            } else {
                $user_id = null;
            }
        }

        // 
        if (!empty($user_id)) {
            if ($state_payload->mode == 'test') {
                $print_user = get_user_by('id', $user_id);
                if ($create_user) {
                    $message = "User created/found in the database - not logged in because of test mode";
                } else
                    if ($user_created) {
                        $message = "User found in the database by email - not logged in because of test mode but updated the user meta with the UID";
                    } else {
                        $message = "User found in the database - not logged in because of test mode";
                    }

                $result = array(
                    'message' => $message,
                    'user_id' => $user_id,
                    'sso_user' => $wp_user,
                    'status_window' => 'keep_open'
                );
            } else {
                try {
                    wp_set_auth_cookie($user_id, true, '');
                    do_action('hbm_auth_new_action', $user, $state_payload);
                } catch (Exception $e) {
                    error_log('Error in wp_set_auth_cookie: ' . $e->getMessage());
                }
                $result = array(
                    'message' => "User found in the database - and logged in successfully",
                    'user_id' => $user_id,
                    'sso_user' => $wp_user,
                    'status_window' => 'close'
                );
            }
        } else {
            if ($state_payload->mode == 'test') {
                if ($create_user) {
                    $result = array(
                        'message' => "In test mode - user not found or created - check the users menu in the admin panel",
                        'user_id' => "not found",
                        'status_window' => 'keep_open'
                    );
                } else {
                    $result = array(
                        'message' => "In test mode - user is not created as intended",
                        'user_id' => "not found",
                        'status_window' => 'keep_open'
                    );
                }
            } else {
                // Send message to the user that the login failed
                $result = array(
                    'message' => '<p><bold>Login failed because the user isn\'t created in this application :  </bold></p>'
                    . "<br>" . '<p><bold>Please contact the administrator and close this window. </bold></p>',
                    'user_id' => "not found",
                    'status_window' => 'keep_open'
                );
            }
        }
        return $result + array('identifier' => $state_payload->identifier);
    }
    /**
     * Handle user profile edit using OAuth2 data.
     *
     * @param array $user_data Data retrieved from OAuth2 provider.
     * @return WP_User|WP_Error User object on success, error object on failure.
     */
    function handle_profile_edit($state_payload, $framework_user)
    {
        // Implement profile edit logic here
        return 'Profile edit action received: ' . json_encode($state_payload) . '<br>' . 'Framework user: ' . json_encode($framework_user);
    }

    public function handle_logout(WP_REST_Request $request)
    {
        $state_urlcoded = $request->get_param('state');
        $state = urldecode($state_urlcoded);
        $state_payload = $this->secret_manager->decode_jwt($state);
        if ($state_payload->action == 'logout') {
            if ($state_payload->mode == 'live') {
                wp_clear_auth_cookie();
                do_action('hbm_auth_new_action', null, (object) $state_payload);
            } else {
                $message = "<h3>You are on the Client Server</h3>"
                    . "<p>Because of TEST we didn't actually logout the current user in WP.</p>"
                    . "<p>Logout request received: </p><pre>" . json_encode($state_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "</pre>";
                echo_modal(null, $message);
            }
            do_action('hbm_logout_sso_user');
        }

    }
    function create_update_user($wp_user, $user_id = null)
    {
        $framework_context = apply_filters('hbm_get_framework_context', '');
        if (!$user_id) {
            $random_password = wp_generate_password(12, false);
            $user_id = wp_create_user($wp_user->user_login, $random_password, $wp_user->email);

        }

        $user = get_user_by('id', $user_id);
        $user->display_name = $wp_user->first_name . ' ' . $wp_user->last_name;
        $user->first_name = $wp_user->first_name;
        $user->last_name = $wp_user->last_name;
        $updated_user = wp_update_user($user);
        if (is_wp_error($updated_user)) {
            error_log('Error in updating the user: ' . $updated_user->get_error_message());
        }

        // Store the UID in user meta
        add_user_meta($user_id, '_' . $framework_context->metadata, $wp_user->auth_id, true);
        return $user_id;
    }
}