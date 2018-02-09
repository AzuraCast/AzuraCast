<?php
return [
    'method' => 'post',
    'elements' => [

        'streamer_username' => [
            'text',
            [
                'label' => _('Streamer Username'),
                'description' => _('The streamer will use this username to connect to the radio server.'),
                'required' => true,
            ]
        ],

        'streamer_password' => [
            'text',
            [
                'label' => _('Streamer Password'),
                'description' => _('The streamer will use this password to connect to the radio server. Do not use the colon (:) character.'),
                'required' => true,
                'filter' => function($text) {
                    return str_replace(':', '', trim($text));
                },
            ]
        ],

        'display_name' => [
            'text',
            [
                'label' => _('Streamer Display Name'),
                'description' => _('This is the informal display name that will be shown in API responses if the streamer/DJ is live.'),
            ]
        ],

        'comments' => [
            'textarea',
            [
                'label' => _('Comments'),
                'description' => _('Internal notes or comments about the user, visible only on this control panel.'),
            ]
        ],

        'is_active' => [
            'radio',
            [
                'label' => _('Account is Active'),
                'description' => _('Set to "Yes" to allow this account to log in and stream.'),
                'required' => true,
                'default' => '1',
                'options' => [
                    0 => _('No'),
                    1 => _('Yes'),
                ],
            ]
        ],

        'submit' => [
            'submit',
            [
                'type' => 'submit',
                'label' => _('Save Changes'),

                'class' => 'ui-button btn-lg btn-primary',
            ]
        ],

    ],
];