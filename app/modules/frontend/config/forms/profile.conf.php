<?php
/**
 * Profile Form
 */

return [
    'method' => 'post',
    'groups' => [

        'account_info' => [
            'legend' => 'Account Information',
            'elements' => [

                /*
                'name' => array('text', array(
                    'label' => 'Your Name',
                    'class' => 'half-width',
                    'required' => true,
                )),
                */

                'email' => ['text', [
                    'label' => 'E-mail Address',
                    'class' => 'half-width',
                    'required' => true,
                    'autocomplete' => 'off',
                ]],

                'auth_password' => ['password', [
                    'label' => 'Reset Password',
                    'description' => 'To change your password, enter the new password in the field below.',
                    'autocomplete' => 'off',
                ]],

            ],
        ],

        'submit' => [
            'elements' => [
                'submit' => ['submit', [
                    'type' => 'submit',
                    'label' => 'Save Profile',
                    'helper' => 'formButton',
                    'class' => 'btn btn-lg btn-primary',
                ]],
            ],
        ],

    ],
];