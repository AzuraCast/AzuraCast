<?php
return [
    'method' => 'post',
    'elements' => [

        'streamer_username' => ['text', [
            'label' => 'Login Username',
            'description' => 'The streamer will use this username to connect to the radio server.',
            'required' => true,
        ]],

        'streamer_password' => ['text', [
            'label' => 'Login Password',
            'description' => 'The streamer will use this password to connect to the radio server.',
            'required' => true,
        ]],

        'comments' => ['textarea', [
            'label' => 'Account Comments',
            'description' => 'Internal notes or comments about the user, visible only on this control panel.',
        ]],

        'is_active' => ['radio', [
            'label' => 'Account is Active',
            'description' => 'Set to "Yes" to allow this account to log in and stream.',
            'required' => true,
            'default' => '1',
            'options' => [
                0 => 'No',
                1 => 'Yes',
            ],
        ]],

        'submit' => ['submit', [
            'type' => 'submit',
            'label' => 'Save Changes',
            'helper' => 'formButton',
            'class' => 'ui-button btn-lg btn-primary',
        ]],

    ],
];