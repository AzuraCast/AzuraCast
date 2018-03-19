<?php
return [
    'method' => 'post',
    'enctype' => 'multipart/form-data',

    'groups' => [

        'profile' => [
            'legend' => __('New Station Details'),
            'elements' => [

                'name' => [
                    'text',
                    [
                        'label' => __('Station Name'),
                        'class' => 'half-width',
                        'required' => true,
                    ]
                ],

                'description' => [
                    'textarea',
                    [
                        'label' => __('Station Description'),
                        'class' => 'full-width full-height',
                    ]
                ],

            ],
        ],

        'cloning' => [
            'legend' => __('Customize Station Cloning'),
            'elements' => [

                'clone_media' => [
                    'radio',
                    [
                        'label' => __('Copy Media?'),
                        'description' => __('Choose how media should be duplicated from the old station.'),
                        'options' => [
                            'none' => __('Do not share or copy media between the stations'),
                            'share' => __('Share the same folder on disk between the stations'),
                            'copy' => __('Copy the existing station\'s media to the new station'),
                        ],
                        'default' => 'none',
                    ]
                ],

                'clone_playlists' => [
                    'radio',
                    [
                        'label' => __('Copy Playlists?'),
                        'options' => [
                            0 => __('No'),
                            1 => __('Yes'),
                        ],
                        'default' => 0,
                    ]
                ],

                'clone_streamers' => [
                    'radio',
                    [
                        'label' => __('Copy Streamer/DJ Accounts?'),
                        'options' => [
                            0 => __('No'),
                            1 => __('Yes'),
                        ],
                        'default' => 0,
                    ]
                ],

                'clone_permissions' => [
                    'radio',
                    [
                        'label' => __('Copy Permissions?'),
                        'description' => __('Selecting "Yes" will assign any users with permissions to the current station to have permissions to the new one.'),
                        'options' => [
                            0 => __('No'),
                            1 => __('Yes'),
                        ],
                        'default' => 0,
                    ]
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
                    ]
                ],
            ],
        ],
    ],
];