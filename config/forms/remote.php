<?php
return [
    'method' => 'post',
    'enctype' => 'multipart/form-data',

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
                        'label' => __('Remote Station Base URL'),
                        'description' => __('Example: if the remote radio URL is http://station.example.com:8000/stream.mp3, enter <code>http://station.example.com:8000</code>.'),
                        'required' => true,
                    ]
                ],

                'mount' => [
                    'text',
                    [
                        'label' => __('Remote Station Mountpoint/SID'),
                        'description' => __('Specify a mountpoint (i.e. <code>/radio.mp3</code>) or a Shoutcast SID (i.e. <code>2</code>) to specify a specific stream to use.'),
                    ]
                ],

                'enable_autodj' => [
                    'radio',
                    [
                        'label' => __('Enable AutoDJ'),
                        'description' => __('If set to "Yes", the AutoDJ will automatically play music to this mount point.'),
                        'choices' => [0 => __('No'), 1 => __('Yes')],
                        'default' => 0,
                    ]
                ],

                'autodj_format' => [
                    'radio',
                    [
                        'label' => __('AutoDJ Format'),
                        'choices' => [
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
