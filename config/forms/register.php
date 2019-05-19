<?php
return [
    'method' => 'post',
    'groups' => [

        'account' => [
            'legend' => __('Account Information'),
            'elements' => [

                'username' => [
                    'text',
                    [
                        'label' => __('E-mail Address'),
                        'class' => 'half-width',
                        'required' => true,
                        'validators' => ['EmailAddress'],
                    ]
                ],

                'password' => [
                    'password',
                    [
                        'label' => __('Password'),
                        'required' => true,
                    ]
                ],

            ],
        ],

        'submit' => [
            'elements' => [
                'submit' => [
                    'submit',
                    [
                        'type' => 'submit',
                        'label' => __('Create Account'),
                        'class' => 'btn btn-lg btn-primary',
                    ]
                ],
            ],
        ],

    ],
];
