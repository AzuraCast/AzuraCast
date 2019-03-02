<?php
use App\Entity\StationRemote;

return [
    'groups' => [

        'basic_info' => [
            'elements' => [

                'type' => [
                    'radio',
                    [
                        'label' => __('Remote Station Type'),
                        'required' => true,
                        'choices' => [
                            'shoutcast1' => 'SHOUTcast v1',
                            'shoutcast2' => 'SHOUTcast v2',
                            'icecast' => 'Icecast v2.4+',
                        ],
                    ]
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
                    ]
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
                    ]
                ],

                'enable_autodj' => [
                    'radio',
                    [
                        'label' => __('Broadcast AutoDJ to Remote Station'),
                        'description' => __('If set to "Yes", the AutoDJ on this installation will automatically play music to this mount point.'),
                        'choices' => [0 => __('No'), 1 => __('Yes')],
                        'default' => 0,
                    ]
                ],

            ],
        ],

        'autodj' => [
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
                    ]
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
                    ]
                ],

                'source_port' => [
                    'text',
                    [
                        'label' => __('Remote Station Source Port'),
                        'description' => __('If the port you broadcast to is different from the one you listed in the URL above, specify the source port here.'),
                    ]
                ],

                'source_mount' => [
                    'text',
                    [
                        'label' => __('Remote Station Source Mountpoint/SID'),
                        'description' => __('If the mountpoint (i.e. <code>/radio.mp3</code>) or Shoutcast SID (i.e. <code>2</code>) you broadcast to is different from the one listed above, specify the source mount point here.'),
                    ]
                ],

                'source_username' => [
                    'text',
                    [
                        'label' => __('Remote Station Source Username'),
                        'description' => __('If you are broadcasting using AutoDJ, enter the source username here. This may be blank.'),
                    ]
                ],

                'source_password' => [
                    'text',
                    [
                        'label' => __('Remote Station Source Password'),
                        'description' => __('If you are broadcasting using AutoDJ, enter the source password here.'),
                    ]
                ],

                'is_public' => [
                    'radio',
                    [
                        'label' => __('Advertise to YP Directories (Public Station)'),
                        'description' => __('Set to "yes" to advertise this stream on the YP public radio directories.'),
                        'choices' => [0 => __('No'), 1 => __('Yes')],
                        'default' => 0,
                    ]
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
                    ]
                ],

            ],
        ],
    ],
];
