<?php
return [
    'elements' => [

        'username' => [
            'text',
            [
                'label' => __('Username'),
                'class' => 'half-width',
                'maxLength' => 32,
            ],
        ],

        'password' => [
            'password',
            [
                'label' => __('New Password'),
                'description' => __('Leave blank to use the current password.'),
                'autocomplete' => 'off',
                'required' => false,
            ],
        ],

        'publicKeys' => [
            'textarea',
            [
                'label' => __('SSH Public Keys'),
                'class' => 'text-preformatted',
                'description' => __('Optionally supply SSH public keys this user can use to connect instead of a password. Enter one key per line.'),
                'required' => false,
            ],
        ],

        'submit' => [
            'submit',
            [
                'type' => 'submit',
                'label' => __('Save Changes'),
                'class' => 'btn btn-lg btn-primary',
            ],
        ],
    ],
];
