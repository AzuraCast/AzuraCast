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
                        'label_class' => 'mb-2',
                        'form_group_class' => 'col-md-6 mt-1',
                    ]
                ],

                'password' => [
                    'password',
                    [
                        'label' => __('Password'),
                        'required' => true,
                        'label_class' => 'mb-2',
                        'form_group_class' => 'col-md-6 mt-1',
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
                        'form_group_class' => 'col-sm-12 mt-3',
                    ]
                ],
            ],
        ],

    ],
];
