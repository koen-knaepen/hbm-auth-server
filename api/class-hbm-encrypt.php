<?php
use \Firebase\JWT\JWT;
use Firebase\JWT\Key;

class HBM_Server_JWT_Manager
{
    private $secret_key_option_name = 'hbm_jwt_secret_key';

    public function __construct()
    {
        // Initialization tasks
        // $this->get_Session_DB();
    }

    // function get_Session_DB()
    // {
    //     global $wpdb;
    //     $table_name = $wpdb->prefix . 'hbm_auth_sessions';
    //     $charset_collate = $wpdb->get_charset_collate();
    //     $create_ddl = "CREATE TABLE IF NOT EXISTS $table_name (
    //         id bigint(20) NOT NULL AUTO_INCREMENT,
    //         session_id varchar(255) NOT NULL,
    //         session_key varchar(255) NOT NULL,
    //         session_value varchar(255) NOT NULL,
    //         PRIMARY KEY  (id) )  $charset_collate;";
    //     maybe_create_table($table_name, $create_ddl);
    // }
    public function set_secret_key($prefix = 'hbm-auth-session-')
    {
        $site_url = get_site_url();
        $timestamp = time();
        $random_string = carbon_get_theme_option('hbm-auth-subscription-client-id') . wp_generate_password(8, false, false);
        $secret = hash('sha256', $site_url . $timestamp . $random_string);

        $identifier = uniqid($prefix);
        set_transient($identifier, $secret, 5 * MINUTE_IN_SECONDS);
        return $identifier;
    }

    public function get_secret_key($identifier, $transient = true)
    {
        $key = get_transient($identifier);
        if (!$transient) {
            delete_transient($identifier);
        }
        return $key;
    }

    /**
     * Summary of encode_jwt
     * !!! If transient is set to false, the secret key will be deleted from the database immediatly !!!
     * !!! This means that a JWT token is created but can never be verified !!!
     * 
     * @param mixed $payload
     * @param mixed $prefix
     * @param mixed $transient
     * @return bool|string
     */
    public function encode_jwt($payload, $prefix = 'hbm-auth-session-', $transient = true)
    {
        // first check if the secret key is set
        $identifier = $this->set_secret_key($prefix);
        // get the secret key
        $secret = $this->get_secret_key($identifier, $transient);
        // encode the payload and add the identifier
        $jwt = JWT::encode(array('identifier' => $identifier) + $payload, $secret, 'HS256');
        $response = array('jwt' => $jwt, 'identifier' => $identifier);
        return json_encode($response);
    }

    public function decode_jwt($jwt)
    {
        try {
            list($header, $payload, $signature) = explode(".", $jwt);
            $decoded_payload = base64_decode($payload, false);
            $unverified_payload = json_decode($decoded_payload);
            $identifier = $unverified_payload->identifier;
            $secret = $this->get_secret_key($identifier);
            // Notice that we're not passing the algorithms as the third argument anymore.
            $decoded = JWT::decode($jwt, new Key($secret, 'HS256'));
            delete_transient($identifier);

            return $decoded;
        } catch (Exception $e) {
            // Handle the exception (e.g., log the error, return an error message, etc.)
            return 'error in decoding jwt' . $e->getMessage();
        }
    }

}