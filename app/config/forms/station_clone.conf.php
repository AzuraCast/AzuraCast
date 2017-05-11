<?php
return [
    'method' => 'post',
    'enctype' => 'multipart/form-data',

    'groups' => [

        'profile' => [
            'legend' => _('New Station Details'),
            'elements' => [

                'name' => [
                    'text',
                    [
                        'label' => _('Station Name'),
                        'class' => 'half-width',
                        'required' => true,
                    ]
                ],

                'description' => [
                    'textarea',
                    [
                        'label' => _('Station Description'),
                        'class' => 'full-width full-height',
                    ]
                ],

            ],
        ],

        'cloning' => [
            'legend' => _('Customize Station Cloning'),
            'elements' => [

                'clone_media' => [
                    'radio',
                    [
                        'label' => _('Copy Media?'),
                        'description' => _('Choose how media should be duplicated from the old station.'),
                        'options' => [
                            'none' => _('Do not share or copy media between the stations'),
                            'share' => _('Share the same folder on disk between the stations'),
                            'copy' => _('Copy the existing station\'s media to the new station'),
                        ],
                        'default' => 'none',
                    ]
                ],

                'clone_playlists' => [
                    'radio',
                    [
                        'label' => _('Copy Playlists?'),
                        'options' => [
                            0 => _('No'),
                            1 => _('Yes'),
                        ],
                        'default' => 0,
                    ]
                ],

                'clone_streamers' => [
                    'radio',
                    [
                        'label' => _('Copy Streamer/DJ Accounts?'),
                        'options' => [
                            0 => _('No'),
                            1 => _('Yes'),
                        ],
                        'default' => 0,
                    ]
                ],

                'clone_permissions' => [
                    'radio',
                    [
                        'label' => _('Copy Permissions?'),
                        'description' => _('Selecting "Yes" will assign any users with permissions to the current station to have permissions to the new one.'),
                        'options' => [
                            0 => _('No'),
                            1 => _('Yes'),
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
                        'label' => _('Create New Station'),
                        'class' => 'btn btn-lg btn-primary',
                    ]
                ],
            ],
        ],
    ],
];