<?php
return [
    'method' => 'post',
    'enctype' => 'multipart/form-data',

    'groups' => [

        'basic_info' => [
            'elements' => [

                'remote_type' => [
                    'radio',
                    [
                        'label' => _('Remote Station Type'),
                        'required' => true,
                        'options' => [
                            'shoutcast1' => 'ShoutCast v1',
                            'shoutcast2' => 'ShoutCast v2',
                            'icecast' => 'IceCast v2.4+',
                        ],
                    ]
                ],

                'remote_url' => [
                    'text',
                    [
                        'label' => _('Remote Station Base URL'),
                        'description' => _('Example: if the remote radio URL is http://station.example.com:8000/stream.mp3, enter <code>http://station.example.com:8000</code>.'),
                        'required' => true,
                    ]
                ],

                'remote_mount' => [
                    'text',
                    [
                        'label' => _('Remote Station Mountpoint/SID'),
                        'description' => _('Specify a mountpoint (i.e. <code>/radio.mp3</code>) or a Shoutcast SID (i.e. <code>2</code>) to specify a specific stream to use.'),
                    ]
                ],

                'is_default' => [
                    'radio',
                    [
                        'label' => _('Is Default Mount'),
                        'description' => _('If this mount is the default, it will be played on the radio preview and the public radio page in this system.'),
                        'options' => [0 => _('No'), 1 => _('Yes')],
                        'default' => 0,
                    ]
                ],

                'enable_autodj' => [
                    'radio',
                    [
                        'label' => _('Enable AutoDJ'),
                        'description' => _('If set to "Yes", the AutoDJ will automatically play music to this mount point.'),
                        'options' => [0 => _('No'), 1 => _('Yes')],
                        'default' => 0,
                    ]
                ],

                'autodj_format' => [
                    'radio',
                    [
                        'label' => _('AutoDJ Format'),
                        'options' => [
                            'mp3' => 'MP3',
                            'ogg' => 'OGG Vorbis',
                            'aac' => 'AAC+ (MPEG4 HE-AAC v2)',
                        ],
                        'default' => 'mp3',
                    ]
                ],

                'autodj_bitrate' => [
                    'radio',
                    [
                        'label' => _('AutoDJ Bitrate (kbps)'),
                        'options' => [
                            32 => '32',
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

                'remote_source_username' => [
                    'text',
                    [
                        'label' => _('Remote Station Source Username'),
                        'description' => _('If you are broadcasting using AutoDJ, enter the source username here. This may be blank.'),
                    ]
                ],

                'remote_source_password' => [
                    'text',
                    [
                        'label' => _('Remote Station Source Password'),
                        'description' => _('If you are broadcasting using AutoDJ, enter the source password here.'),
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
                        'label' => _('Save Changes'),
                        'class' => 'ui-button btn-lg btn-primary',
                    ]
                ],

            ],
        ],
    ],
];