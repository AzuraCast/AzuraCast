<?php
return [
    'method' => 'post',
    'enctype' => 'multipart/form-data',

    'groups' => [

        'basic_info' => [
            'elements' => [

                'name' => [
                    'text',
                    [
                        'label' => __('Mount Point Name/URL'),
                        'description' => __('This name should always begin with a slash (/), and must be a valid URL, such as /autodj.mp3'),
                        'required' => true,
                    ]
                ],

                'is_default' => [
                    'radio',
                    [
                        'label' => __('Is Default Mount'),
                        'description' => __('If this mount is the default, it will be played on the radio preview and the public radio page in this system.'),
                        'choices' => [0 => __('No'), 1 => __('Yes')],
                        'default' => 0,
                    ]
                ],

                'enable_autodj' => [
                    'radio',
                    [
                        'label' => __('Enable AutoDJ'),
                        'description' => __('If set to "Yes", the AutoDJ will automatically play music to this mount point.'),
                        'choices' => [0 => __('No'), 1 => __('Yes')],
                        'default' => 1,
                    ]
                ],

                'autodj_format' => [
                    'radio',
                    [
                        'label' => __('AutoDJ Format'),
                        'choices' => [
                            'mp3' => 'MP3',
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

                'relay_url' => [
                    'text',
                    [
                        'label' => __('Relay Stream URL'),
                        'description' => __('Enter the full URL of another stream to relay its broadcast through this mount point.'),
                        'default' => '',
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

                'authhash' => [
                    'text',
                    [
                        'label' => __('YP Directory Authorization Hash'),
                        'description' => sprintf(__('If your stream is set to advertise to YP directories above, you must specify an authorization hash. You can manage authhashes <a href="%s" target="_blank">on the SHOUTcast web site</a>.'),
                            'https://rmo.shoutcast.com'),
                        'default' => '',
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