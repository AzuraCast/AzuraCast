<?php
use App\Entity\Station;
use App\Radio\Adapters;

$frontends = Adapters::listFrontendAdapters(true);
$frontend_types = [];
foreach ($frontends as $adapter_nickname => $adapter_info) {
    $frontend_types[$adapter_nickname] = $adapter_info['name'];
}

$backends = Adapters::listBackendAdapters(true);
$backend_types = [];
foreach ($backends as $adapter_nickname => $adapter_info) {
    $backend_types[$adapter_nickname] = $adapter_info['name'];
}

return [
    'method' => 'post',
    'enctype' => 'multipart/form-data',

    'groups' => [
        'profile' => [
            'legend' => __('Station Profile'),
            'elements' => [
                'name' => [
                    'text',
                    [
                        'label' => __('Name'),
                        'required' => true,
                    ]
                ],

                'description' => [
                    'textarea',
                    [
                        'label' => __('Description'),
                    ]
                ],

                'genre' => [
                    'text',
                    [
                        'label' => __('Genre'),
                    ]
                ],

                'url' => [
                    'text',
                    [
                        'label' => __('Web Site URL'),
                        'description' => __('Note: This should be the public-facing homepage of the radio station, not the AzuraCast URL. It will be included in broadcast details.'),
                    ]
                ],

                'is_enabled' => [
                    'radio',
                    [
                        'label' => __('Enable Broadcasting'),
                        'description' => __('If disabled, the station will not broadcast or shuffle its AutoDJ.'),
                        'choices' => [
                            0 => __('Disabled'),
                            1 => __('Enabled'),
                        ],
                        'default' => 1,
                    ]
                ],

                'enable_public_page' => [
                    'radio',
                    [
                        'label' => __('Enable Public Page'),
                        'description' => __('Whether to show or hide the station from public pages and general API results.'),
                        'choices' => [
                            0 => __('Disabled'),
                            1 => __('Enabled')
                        ],
                        'default' => 1,
                    ]
                ],

                'short_name' => [
                    'text',
                    [
                        'label' => __('URL Stub'),
                        'label_class' => 'advanced',
                        'description' => __('Optionally specify a short URL-friendly name, such as <code>my_station_name</code>, that will be used in this station\'s URLs. Leave this field blank to automatically create one based on the station name.'),
                    ]
                ],

                'radio_media_dir' => [
                    'text',
                    [
                        'label' => __('Custom Media Directory'),
                        'label_class' => 'advanced',
                        'description' => __('The directory where media files are stored. Leave blank to use default directory.'),
                    ]
                ],

                'api_history_items' => [
                    'select',
                    [
                        'label' => __('Number of Recently Played Songs'),
                        'label_class' => 'advanced',
                        'description' => __('Customize the number of songs that will appear in the "Song History" section for this station and in all public APIs.'),
                        'choices' => [
                            0 => __('Disabled'),
                            1 => '1',
                            5 => '5',
                            10 => '10',
                            15 => '15',
                        ],
                        'default' => Station::DEFAULT_API_HISTORY_ITEMS,
                    ]
                ]
            ],
        ],

        'select_frontend_type' => [
            'legend' => __('Select Broadcasting Service'),

            'elements' => [
                'frontend_type' => [
                    'radio',
                    [
                        'label' => __('Broadcasting Service'),
                        'description' => __('This software delivers your broadcast to the listening audience.'),
                        'options' => $frontend_types,
                        'default' => Adapters::DEFAULT_FRONTEND,
                    ]
                ],
            ],
        ],

        'frontend_local' => [
            'legend' => __('Configure Broadcasting Service'),
            'class' => 'frontend_fieldset',

            'elements' => [

                'port' => [
                    'text',
                    [
                        'label' => __('Customize Broadcasting Port'),
                        'label_class' => 'advanced',
                        'description' => __('No other program can be using this port. Leave blank to automatically assign a port.'),
                        'belongsTo' => 'frontend_config',
                        'class' => 'input-port',
                    ]
                ],

                'source_pw' => [
                    'text',
                    [
                        'label' => __('Customize Source Password'),
                        'label_class' => 'advanced',
                        'description' => __('Leave blank to automatically generate a new password.'),
                        'belongsTo' => 'frontend_config',
                    ]
                ],

                'admin_pw' => [
                    'text',
                    [
                        'label' => __('Customize Administrator Password'),
                        'label_class' => 'advanced',
                        'description' => __('Leave blank to automatically generate a new password.'),
                        'belongsTo' => 'frontend_config',
                    ]
                ],

                'max_listeners' => [
                    'text',
                    [
                        'label' => __('Maximum Listeners'),
                        'label_class' => 'advanced',
                        'description' => __('Maximum number of total listeners across all streams. Leave blank to use the default (250).'),
                        'belongsTo' => 'frontend_config',
                    ]
                ],

                'custom_config' => [
                    'textarea',
                    [
                        'label' => __('Custom Configuration'),
                        'label_class' => 'advanced',
                        'belongsTo' => 'frontend_config',
                        'class' => 'text-preformatted',
                        'description' => __('This code will be included in the frontend configuration. You can use either JSON {"new_key": "new_value"} format or XML &lt;new_key&gt;new_value&lt;/new_key&gt;.'),
                    ]
                ],

            ],
        ],

        'select_backend_type' => [
            'legend' => __('Select AutoDJ Service'),

            'elements' => [
                'backend_type' => [
                    'radio',
                    [
                        'label' => __('AutoDJ Service'),
                        'description' => __('This software shuffles from playlists of music constantly and plays when no other radio source is available.'),
                        'options' => $backend_types,
                        'default' => Adapters::DEFAULT_BACKEND,
                    ]
                ],
            ],
        ],

        'backend_liquidsoap' => [
            'legend' => __('Configure Liquidsoap'),
            'class' => 'backend_fieldset',

            'elements' => [

                'enable_streamers' => [
                    'radio',
                    [
                        'label' => __('Allow Streamers / DJs'),
                        'description' => __('If this setting is turned on, streamers (or DJs) will be able to connect directly to your stream and broadcast live music that interrupts the AutoDJ stream.'),
                        'default' => '0',
                        'choices' => [0 => __('No'), 1 => __('Yes')],
                    ]
                ],

                'enable_requests' => [
                    'radio',
                    [
                        'label' => __('Allow Song Requests'),
                        'description' => __('This enables listeners to request a song for play on your station. Only songs that are already in your playlists are listed as requestable.'),
                        'default' => '0',
                        'choices' => [0 => __('No'), 1 => __('Yes')],
                    ]
                ],

                'charset' => [
                    'radio',
                    [
                        'label' => __('Character Set Encoding'),
                        'description' => __('For most cases, use the default UTF-8 encoding. The older ISO-8859-1 encoding can be used if accepting connections from SHOUTcast 1 DJs or using other legacy software.'),
                        'belongsTo' => 'backend_config',
                        'default' => 'UTF-8',
                        'choices' => [
                            'UTF-8' => 'UTF-8',
                            'ISO-8859-1' => 'ISO-8859-1',
                        ],
                        'class' => 'field-advanced',
                    ],
                ],

                'request_delay' => [
                    'number',
                    [
                        'label' => __('Request Minimum Delay (Minutes)'),
                        'description' => __('If requests are enabled, this specifies the minimum delay (in minutes) between a request being submitted and being played. If set to zero, no delay is applied.<br><b>Important:</b> Some stream licensing rules require a minimum delay for requests (in the US, this is currently 60 minutes). Check your local regulations for more information.'),
                        'default' => Station::DEFAULT_REQUEST_DELAY,
                        'min' => '0',
                        'max' => '1440',
                    ]
                ],

                'request_threshold' => [
                    'number',
                    [
                        'label' => __('Request Last Played Threshold (Minutes)'),
                        'description' => __('If requests are enabled, this specifies the minimum time (in minutes) between a song playing on the radio and being available to request again. Set to 0 for no threshold.'),
                        'default' => Station::DEFAULT_REQUEST_THRESHOLD,
                        'min' => '0',
                        'max' => '1440',
                    ]
                ],

                'crossfade' => [
                    'number',
                    [
                        'label' => __('Crossfade Duration (Seconds)'),
                        'belongsTo' => 'backend_config',
                        'description' => __('Number of seconds to overlap songs. Set to 0 to disable crossfade completely.'),
                        'default' => 2,
                        'min' => '0.0',
                        'max' => '30.0',
                        'step' => '0.1',
                    ]
                ],
                
                'disconnect_deactivate_streamer' => [
                    'number',
                    [
                        'label' => __('Deactivate Streamer on Disconnect (Seconds)'),
                        'description' => __('Number of seconds to deactivate station streamer on manual disconnect. Set to 0 to disable deactivation completely.'),
                        'default' => 0,
                        'min' => '0',
                        'step' => '1',
                    ]
                ],

                'use_manual_autodj' => [
                    'radio',
                    [
                        'label' => __('Manual AutoDJ Mode'),
                        'label_class' => 'advanced',
                        'description' => __('This mode disables App\'s AutoDJ management, using Liquidsoap itself to manage song playback. "Next Song" and some other features will not be available.'),
                        'default' => '0',
                        'choices' => [0 => __('No'), 1 => __('Yes')],
                        'belongsTo' => 'backend_config',
                    ]
                ],

                'dj_port' => [
                    'text',
                    [
                        'label' => __('Customize DJ/Streamer Port'),
                        'label_class' => 'advanced',
                        'description' => __('No other program can be using this port. Leave blank to automatically assign a port.<br><b>Note:</b> The port after this one (n+1) will automatically be used for legacy connections.'),
                        'belongsTo' => 'backend_config',
                        'class' => 'input-port',
                    ]
                ],

                'dj_buffer' => [
                    'text',
                    [
                        'label' => __('DJ/Streamer Buffer Time (Seconds)'),
                        'label_class' => 'advanced',
                        'description' => __('The number of seconds of signal to store in case of interruption. Set to the lowest value that your DJs can use without stream interruptions.'),
                        'default' => 5,
                        'belongsTo' => 'backend_config',
                    ]
                ],

                'telnet_port' => [
                    'text',
                    [
                        'label' => __('Customize Internal Request Processing Port'),
                        'label_class' => 'advanced',
                        'description' => __('This port is not used by any external process. Only modify this port if the assigned port is in use. Leave blank to automatically assign a port.'),
                        'belongsTo' => 'backend_config',
                        'class' => 'input-port',
                    ]
                ],

                'custom_config' => [
                    'textarea',
                    [
                        'label' => __('Custom Configuration'),
                        'label_class' => 'advanced',
                        'belongsTo' => 'backend_config',
                        'class' => 'text-preformatted',
                        'description' => __('This code will be inserted into your station\'s Liquidsoap configuration, below the playlist configuration and just before the Icecast output. Only use valid Liquidsoap code for this section!'),
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
                        'label' => __('Save Changes'),
                        'class' => 'btn btn-lg btn-primary',
                    ]
                ],
            ],
        ],
    ],
];
