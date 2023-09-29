<?php

namespace HBM\auth_server;

function init_spinner($cb_name, $js_name, $action_name, $help_text, $button_text, $valid_message)
{
    $spinner_fields =
        array(
            \Carbon_Fields\Field::make('html', "hbm-auth-{$cb_name}-button") // Button to test the Entra configuration
                ->set_html("<button type='button' id='hbm-{$js_name}'>{$button_text}</button>")
                ->set_classes("hbm-{$js_name}-wrapper")
                ->set_help_text("{$help_text}"),
            \Carbon_Fields\Field::make('html', "hbm-auth-{$cb_name}-spinner") // Button to test the Entra configuration
                ->set_html('<div class="hbm-spinner"></div>')
                ->set_classes("hbm-{$js_name}-spinner"),
            \Carbon_Fields\Field::make('html', "hbm-auth-{$cb_name}-message") // Message to display if the Entra configuration is valid (hidden by default)
                ->set_html("<p>{$valid_message}</p>")
                ->set_classes("hbm-validMessage hbm-{$js_name}-message")
        );
    return $spinner_fields;
}
