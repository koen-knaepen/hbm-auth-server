<?php

namespace HBM\auth_server;

// require_once HBM_PLUGIN_PATH . 'api/framework-aws-cognito/class-cognito.php';

abstract class HBM_Auth_Framework
{

    private static $instances = [];

    public static function get_instance($framework = '')
    {
        $class = get_called_class(); // Default to the called class

        // If a framework is specified, determine the appropriate child class
        if ($framework) {
            switch ($framework) {
                case 'cognito':
                    $class = __NAMESPACE__ . '\HBM_Framework_Cognito';
                    break;
                    // Add more cases for other frameworks as needed
            }
        }

        if (!isset(self::$instances[$class])) {
            self::$instances[$class] = new $class();
        }

        return self::$instances[$class];
    }

    protected function __construct()
    {
    }
    private function __clone()
    {
    }
    final public function __wakeup()
    {
    }

    final public function get_framework_context()
    {
        $context = $this->set_context();

        // Ensure the required keys are present
        $requiredKeys = ['name', 'label', 'auth_id_name'];
        foreach ($requiredKeys as $key) {
            if (!array_key_exists($key, $context)) {
                throw new \Exception("Key {$key} is missing in the context.");
            }
        }

        return (object) $context;
    }


    // Abstract methods that child classes must implement
    protected function set_context(): array
    {
        $context = array(
            'name' => 'unknown',
            'label' => 'NO FRAMEWORK',
            'metadata' => "hbm_none_id",
            'auth_id_name' => '',
        );
        return $context;
    }

    abstract public function create_auth_endpoint($action, $redirect_url, $jwt, $application);
    abstract public function exchange_code_for_tokens($code, $application);
    abstract public function transform_to_wp_user($retrieved_user, $payload);
    // ... other abstract methods ...
}
