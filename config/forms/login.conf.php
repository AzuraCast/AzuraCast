<?php
return [
    'method' => 'post',
    'elements' => [

        'username' => [
            'text',
            [
                'label' => __('E-mail Address'),
                'class' => 'half-width',
                'spellcheck' => 'false',
                'required' => true,
            ]
        ],

        'password' => [
            'password',
            [
                'label' => __('Password'),
                'class' => 'half-width',
                'required' => true,
            ]
        ],

        'submit' => [
            'submit',
            [
                'type' => 'submit',
                'label' => __('Log in'),
                'class' => 'btn btn-lg btn-primary',
            ]
        ],
    ],
];