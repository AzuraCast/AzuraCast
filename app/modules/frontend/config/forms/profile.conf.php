<?php
/**
 * Profile Form
 */

return [
    'method' => 'post',
    'groups' => [

        'account_info' => [
            'legend' => _('Account Information'),
            'elements' => [

                /*
                'name' => array('text', array(
                    'label' => 'Your Name',
                    'class' => 'half-width',
                    'required' => true,
                )),
                */

                'email' => ['text', [
                    'label' => _('E-mail Address'),
                    'class' => 'half-width',
                    'required' => true,
                    'autocomplete' => 'off',
                ]],

                'auth_password' => ['password', [
                    'label' => _('Reset Password'),
                    'description' => _('To change your password, enter the new password in the field below.'),
                    'autocomplete' => 'off',
                ]],

            ],
        ],

        'submit' => [
            'elements' => [
                'submit' => ['submit', [
                    'type' => 'submit',
                    'label' => _('Save Profile'),
                    'helper' => 'formButton',
                    'class' => 'btn btn-lg btn-primary',
                ]],
            ],
        ],

    ],
];