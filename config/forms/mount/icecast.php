<?php
use App\Entity\StationMount;

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
                    'toggle',
                    [
                        'label' => __('Set as Default Mount Point'),
                        'description' => __('If this mount is the default, it will be played on the radio preview and the public radio page in this system.'),
                        'selected_text' => __('Yes'),
                        'deselected_text' => __('No'),
                        'default' => false,
                    ]
                ],

                'fallback_mount' => [
                    'text',
                    [
                        'label' => __('Fallback Mount'),
                        'description' => __('If this mount point is not playing audio, listeners will automatically be redirected to this mount point. The default is /error.mp3, a repeating error message.'),
                        'default' => '/error.mp3',
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

                'enable_autodj' => [
                    'toggle',
                    [
                        'label' => __('Enable AutoDJ'),
                        'description' => __('If enabled, the AutoDJ will automatically play music to this mount point.'),
                        'selected_text' => __('Yes'),
                        'deselected_text' => __('No'),
                        'default' => true,
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
                            StationMount::FORMAT_MP3 => 'MP3',
                            StationMount::FORMAT_OGG => 'OGG Vorbis',
                            StationMount::FORMAT_OPUS => 'OGG Opus',
                            StationMount::FORMAT_AAC => 'AAC+ (MPEG4 HE-AAC v2)',
                        ],
                        'default' => StationMount::FORMAT_MP3,
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


            ],
        ],

        'advanced_items' => [
            'legend' => __('Advanced Configuration'),
            'elements' => [

                'custom_listen_url' => [
                    'text',
                    [
                        'label' => __('Custom Stream URL'),
                        'label_class' => 'advanced',
                        'description' => __('You can set a custom URL for this stream that AzuraCast will use when referring to it. Leave empty to use the default value.')
                    ]
                ],

                'frontend_config' => [
                    'textarea',
                    [
                        'label' => __('Custom Frontend Configuration'),
                        'label_class' => 'advanced',
                        'description' => __('You can include any special mount point settings here, in either JSON { key: \'value\' } format or XML &lt;key&gt;value&lt;/key&gt;'),
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
