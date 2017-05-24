<?php
/**
 * Login Form
 */

return [
    'method' => 'post',
    'elements' => [

        'username' => [
            'text',
            [
                'label' => _('E-mail Address'),
                'class' => 'half-width',
                'spellcheck' => 'false',
                'required' => true,
            ]
        ],

        'password' => [
            'password',
            [
                'label' => _('Password'),
                'class' => 'half-width',
                'required' => true,
            ]
        ],

        'submit' => [
            'submit',
            [
                'type' => 'submit',
                'label' => _('Log in'),
                'class' => 'btn btn-lg btn-primary',
            ]
        ],
    ],
];