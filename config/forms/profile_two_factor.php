<?php
return [
    'method' => 'post',
    'elements' => [
        'secret' => ['hidden', []],

        'otp' => [
            'text',
            [
                'label' => __('Code from Authenticator App'),
                'description' => __('Enter the current code provided by your authenticator app to verify that it\'s working correctly.'),
                'class' => 'half-width',
                'required' => true,
                'label_class' => 'mb-2',
                'form_group_class' => 'col-sm-12 mt-3',
            ]
        ],

        'submit' => [
            'submit',
            [
                'type' => 'submit',
                'label' => __('Verify Authenticator'),
                'class' => 'btn btn-lg btn-primary',
                'form_group_class' => 'col-sm-12 mt-3',
            ],
        ],

    ],
];
