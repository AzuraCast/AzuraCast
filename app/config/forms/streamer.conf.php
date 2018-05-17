<?php
return [
    'method' => 'post',
    'elements' => [

        'streamer_username' => [
            'text',
            [
                'label' => __('Streamer Username'),
                'description' => __('The streamer will use this username to connect to the radio server.'),
                'required' => true,
            ]
        ],

        'streamer_password' => [
            'text',
            [
                'label' => __('Streamer Password'),
                'description' => __('The streamer will use this password to connect to the radio server. Do not use the colon (:) character.'),
                'required' => true,
                'filter' => function($text) {
                    return str_replace(':', '', trim($text));
                },
            ]
        ],

        'display_name' => [
            'text',
            [
                'label' => __('Streamer Display Name'),
                'description' => __('This is the informal display name that will be shown in API responses if the streamer/DJ is live.'),
            ]
        ],

        'comments' => [
            'textarea',
            [
                'label' => __('Comments'),
                'description' => __('Internal notes or comments about the user, visible only on this control panel.'),
            ]
        ],

        'is_active' => [
            'radio',
            [
                'label' => __('Account is Active'),
                'description' => __('Set to "Yes" to allow this account to log in and stream.'),
                'required' => true,
                'default' => '1',
                'choices' => [
                    0 => __('No'),
                    1 => __('Yes'),
                ],
            ]
        ],

        'submit' => [
            'submit',
            [
                'type' => 'submit',
                'label' => __('Save Changes'),

                'class' => 'ui-button btn-lg btn-primary',
            ]
        ],

    ],
];