<?php

namespace HBM\auth_server;

use HBM\Instantiations\HBM_Class_Handler;

// require_once HBM_PLUGIN_PATH . 'api/framework-aws-cognito/class-cognito.php';

abstract class HBM_Auth_Framework extends HBM_Class_Handler
{
    protected $settings;

    final public function __construct()
    {
        $this->add_to_dna([$this, 'init_pofs']);
    }
    public function init_pofs()
    {
        $this->settings = $this->pof('settings');
    }
    protected static function set_pattern($options = []): array
    {
        $framework = $options['framework'] ?? '';
        if ($framework) {
            switch ($framework) {
                case 'cognito':
                    $class = __NAMESPACE__ . '\HBM_Framework_Cognito';
                    break;
                    // Add more cases for other frameworks as needed
            }
        }
        return [
            'pattern' => 'singleton',
            'class' => $class,
            '__inject' => [
                'WPSettings:hbmAuthServerSettings?settings',
            ]
        ];
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
