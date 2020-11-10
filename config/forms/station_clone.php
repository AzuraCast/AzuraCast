<?php
return [
    'method' => 'post',
    'enctype' => 'multipart/form-data',

    'groups' => [

        'profile' => [
            'elements' => [

                'name' => [
                    'text',
                    [
                        'label' => __('New Station Name'),
                        'class' => 'half-width',
                        'required' => true,
                    ],
                ],

                'description' => [
                    'textarea',
                    [
                        'label' => __('New Station Description'),
                        'class' => 'full-width full-height',
                    ],
                ],

            ],
        ],

        'cloning' => [
            'use_grid' => true,
            'legend' => __('Customize Station Cloning'),
            'elements' => [

                'clone_media' => [
                    'radio',
                    [
                        'label' => __('Copy Media?'),
                        'description' => __('Choose how media should be duplicated from the old station.'),
                        'choices' => [
                            'none' => __('Do not share media between the stations'),
                            'share' => __('Share the same folder on disk between the stations'),
                        ],
                        'form_group_class' => 'col-sm-12',
                        'default' => 'none',
                    ],
                ],

                'clone_playlists' => [
                    'radio',
                    [
                        'label' => __('Copy Playlists?'),
                        'choices' => [
                            0 => __('No'),
                            1 => __('Yes'),
                        ],
                        'form_group_class' => 'col-sm-4',
                        'default' => 0,
                    ],
                ],

                'clone_streamers' => [
                    'radio',
                    [
                        'label' => __('Copy Streamer/DJ Accounts?'),
                        'choices' => [
                            0 => __('No'),
                            1 => __('Yes'),
                        ],
                        'default' => 0,
                        'form_group_class' => 'col-sm-4',
                    ],
                ],

                'clone_permissions' => [
                    'radio',
                    [
                        'label' => __('Copy Permissions?'),
                        'description' => __('Selecting "Yes" will assign any users with permissions to the current station to have permissions to the new one.'),
                        'choices' => [
                            0 => __('No'),
                            1 => __('Yes'),
                        ],
                        'default' => 0,
                        'form_group_class' => 'col-sm-4',
                    ],
                ],

            ],
        ],

        'submit_grp' => [
            'elements' => [
                'submit' => [
                    'submit',
                    [
                        'type' => 'submit',
                        'label' => __('Create New Station'),
                        'class' => 'btn btn-lg btn-primary',
                    ],
                ],
            ],
        ],
    ],
];
