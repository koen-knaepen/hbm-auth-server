<?php

class HBM_Auth_Admin_Layout
{

    public function displayFields()
    {
        $fields = array(
            \Carbon_Fields\Field::make('text', 'hbm-auth-login-button-text', 'Text on login button') // Field for the text on the login button
                ->set_help_text('Give the text you want to display on the login button'),
            \Carbon_Fields\Field::make('text', 'hbm-auth-logout-button-text', 'Text on logout button') // Field for the text on the logout button
                ->set_help_text('Give the text you want to display on the logout button'),
        );
        return $fields;
    }

}