<?php
return [
    'method' => 'post',
    'groups' => [

        'account' => [
            'legend' => _('Account Information'),
            'elements' => [

                'username' => [
                    'text',
                    [
                        'label' => _('E-mail Address'),
                        'class' => 'half-width',
                        'required' => true,
                        'validators' => ['EmailAddress'],
                    ]
                ],

                'password' => [
                    'password',
                    [
                        'label' => _('Password'),
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
                        'label' => _('Create Account'),
                        'helper' => 'formButton',
                        'class' => 'btn btn-lg btn-primary',
                    ]
                ],
            ],
        ],

    ],
];