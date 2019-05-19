<?php
return [
    'method' => 'post',
    'groups' => [
        [
            'use_grid' => true,
            'elements' => [

                'is_active' => [
                    'toggle',
                    [
                        'label' => __('Account is Active'),
                        'description' => __('Enable to allow this account to log in and stream.'),
                        'selected_text' => __('Yes'),
                        'deselected_text' => __('No'),
                        'default' => true,
                        'form_group_class' => 'col-sm-12',
                    ]
                ],

                'streamer_username' => [
                    'text',
                    [
                        'label' => __('Streamer Username'),
                        'description' => __('The streamer will use this username to connect to the radio server.'),
                        'required' => true,
                        'form_group_class' => 'col-md-6',
                    ]
                ],

                'streamer_password' => [
                    'text',
                    [
                        'label' => __('Streamer Password'),
                        'description' => __('The streamer will use this password to connect to the radio server.'),
                        'required' => true,
                        'form_group_class' => 'col-md-6',
                    ]
                ],

                'display_name' => [
                    'text',
                    [
                        'label' => __('Streamer Display Name'),
                        'description' => __('This is the informal display name that will be shown in API responses if the streamer/DJ is live.'),
                        'form_group_class' => 'col-md-6',
                    ]
                ],

                'comments' => [
                    'textarea',
                    [
                        'label' => __('Comments'),
                        'description' => __('Internal notes or comments about the user, visible only on this control panel.'),
                        'form_group_class' => 'col-md-6',
                    ]
                ],

                'submit' => [
                    'submit',
                    [
                        'type' => 'submit',
                        'label' => __('Save Changes'),

                        'class' => 'ui-button btn-lg btn-primary',
                        'form_group_class' => 'col-sm-12',
                    ]
                ],

            ],
        ]
    ],
];
