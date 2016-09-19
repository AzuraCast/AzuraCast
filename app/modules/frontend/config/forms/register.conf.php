<?php
return [
    'method' => 'post',
    'groups' => [

        'account' => [
            'legend' => 'Account Information',
            'elements' => [

                'username' => ['text', [
                    'label' => 'E-mail Address',
                    'class' => 'half-width',
                    'required' => true,
                    'validators' => ['EmailAddress'],
                ]],

                'password' => ['password', [
                    'label' => 'Password',
                    'required' => true,
                ]],

            ],
        ],

        'submit' => [
            'elements' => [
                'submit' => ['submit', [
                    'type' => 'submit',
                    'label' => 'Create Account',
                    'helper' => 'formButton',
                    'class' => 'btn btn-lg btn-primary',
                ]],
            ],
        ],

    ],
];