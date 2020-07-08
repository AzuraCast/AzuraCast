<?php

use App\Entity\StationRemote;

return [
    'groups' => [

        'basic_info' => [
            'use_grid' => true,
            'elements' => [
                'is_visible_on_public_pages' => [
                    'toggle',
                    [
                        'label' => __('Show on Public Pages'),
                        'description' => __('Enable to allow listeners to select this relay on this station\'s public pages.'),
                        'selected_text' => __('Yes'),
                        'deselected_text' => __('No'),
                        'default' => true,
                        'form_group_class' => 'col-sm-12',
                    ],
                ],

                'type' => [
                    'radio',
                    [
                        'label' => __('Remote Station Type'),
                        'required' => true,
                        'choices' => [
                            App\Radio\Adapters::REMOTE_SHOUTCAST1 => 'SHOUTcast v1',
                            App\Radio\Adapters::REMOTE_SHOUTCAST2 => 'SHOUTcast v2',
                            App\Radio\Adapters::REMOTE_ICECAST => 'Icecast v2.4+',
                        ],
                        'form_group_class' => 'col-sm-12',
                    ],
                ],

                'display_name' => [
                    'text',
                    [
                        'label' => __('Display Name'),
                        'description' => __('The display name assigned to this relay when viewing it on administrative or public pages. Leave blank to automatically generate one.'),
                        'form_group_class' => 'col-md-6',
                    ],
                ],

                'url' => [
                    'text',
                    [
                        'label' => __('Remote Station Listening URL'),
                        'description' => __(
                            'Example: if the remote radio URL is %s, enter <code>%s</code>.',
                            'http://station.example.com:8000/radio.mp3',
                            'http://station.example.com:8000'
                        ),
                        'required' => true,
                        'form_group_class' => 'col-md-6',
                    ],
                ],

                'mount' => [
                    'text',
                    [
                        'label' => __('Remote Station Listening Mountpoint/SID'),
                        'description' => __(
                            'Specify a mountpoint (i.e. <code>%s</code>) or a Shoutcast SID (i.e. <code>%s</code>) to specify a specific stream to use for statistics or broadcasting.',
                            '/radio.mp3',
                            '2'
                        ),
                        'form_group_class' => 'col-md-6',
                    ],
                ],

                'admin_password' => [
                    'text',
                    [
                        'label' => __('Remote Station Administrator Password'),
                        'description' => __('To retrieve detailed unique listeners and client details, an administrator password is often required.'),
                        'form_group_class' => 'col-md-6',
                    ],
                ],

                'enable_autodj' => [
                    'toggle',
                    [
                        'label' => __('Broadcast AutoDJ to Remote Station'),
                        'description' => __('If enabled, the AutoDJ on this installation will automatically play music to this mount point.'),
                        'selected_text' => __('Yes'),
                        'deselected_text' => __('No'),
                        'default' => 0,
                        'form_group_class' => 'col-sm-12',
                    ],
                ],

            ],
        ],

        'autodj' => [
            'use_grid' => true,
            'legend' => __('Configure AutoDJ Broadcasting'),
            'class' => 'fieldset_autodj',

            'elements' => [
                'autodj_format' => [
                    'radio',
                    [
                        'label' => __('AutoDJ Format'),
                        'choices' => [
                            StationRemote::FORMAT_MP3 => 'MP3',
                            StationRemote::FORMAT_OGG => 'OGG Vorbis',
                            StationRemote::FORMAT_AAC => 'AAC+ (MPEG4 HE-AAC v2)',
                        ],
                        'default' => StationRemote::FORMAT_MP3,
                        'form_group_class' => 'col-md-6',
                    ],
                ],

                'autodj_bitrate' => [
                    'radio',
                    [
                        'label' => __('AutoDJ Bitrate (kbps)'),
                        'choices' => [
                            32 => '32',
                            48 => '48',
                            64 => '64',
                            96 => '96',
                            128 => '128',
                            192 => '192',
                            256 => '256',
                            320 => '320',
                        ],
                        'default' => 128,
                        'form_group_class' => 'col-md-6',
                    ],
                ],

                'source_port' => [
                    'text',
                    [
                        'label' => __('Remote Station Source Port'),
                        'description' => __('If the port you broadcast to is different from the one you listed in the URL above, specify the source port here.'),
                        'form_group_class' => 'col-md-6',
                    ],
                ],

                'source_mount' => [
                    'text',
                    [
                        'label' => __('Remote Station Source Mountpoint/SID'),
                        'description' => __('If the mountpoint (i.e. <code>/radio.mp3</code>) or Shoutcast SID (i.e. <code>2</code>) you broadcast to is different from the one listed above, specify the source mount point here.'),
                        'form_group_class' => 'col-md-6',
                    ],
                ],

                'source_username' => [
                    'text',
                    [
                        'label' => __('Remote Station Source Username'),
                        'description' => __('If you are broadcasting using AutoDJ, enter the source username here. This may be blank.'),
                        'form_group_class' => 'col-md-6',
                    ],
                ],

                'source_password' => [
                    'text',
                    [
                        'label' => __('Remote Station Source Password'),
                        'description' => __('If you are broadcasting using AutoDJ, enter the source password here.'),
                        'form_group_class' => 'col-md-6',
                    ],
                ],

                'is_public' => [
                    'toggle',
                    [
                        'label' => __('Publish to "Yellow Pages" Directories'),
                        'description' => __('Enable to advertise this mount point on "Yellow Pages" public radio directories.'),
                        'selected_text' => __('Yes'),
                        'deselected_text' => __('No'),
                        'default' => false,
                        'form_group_class' => 'col-md-6',
                    ],
                ],

            ],
        ],

        'grp_submit' => [
            'elements' => [

                'submit' => [
                    'submit',
                    [
                        'type' => 'submit',
                        'label' => __('Save Changes'),
                        'class' => 'ui-button btn-lg btn-primary',
                    ],
                ],

            ],
        ],
    ],
];
