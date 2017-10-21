<?php
$frontends = \Entity\Station::getFrontendAdapters();
$frontend_types = [];
foreach ($frontends['adapters'] as $adapter_nickname => $adapter_info) {
    $frontend_types[$adapter_nickname] = $adapter_info['name'];
}
$frontend_default = $frontends['default'];

$backends = \Entity\Station::getBackendAdapters();
$backend_types = [];
foreach ($backends['adapters'] as $adapter_nickname => $adapter_info) {
    $backend_types[$adapter_nickname] = $adapter_info['name'];
}
$backend_default = $backends['default'];

return [
    'method' => 'post',
    'enctype' => 'multipart/form-data',

    'groups' => [

        'essentials' => [
            'elements' => [
                'name' => [
                    'text',
                    [
                        'label' => _('Station Name'),
                        'class' => 'half-width',
                        'required' => true,
                    ]
                ],
            ]
        ],

        'profile' => [
            'legend' => _('Station Profile'),
            'elements' => [
                'description' => [
                    'textarea',
                    [
                        'label' => _('Station Description'),
                        'class' => 'full-width full-height',
                    ]
                ],

                'url' => [
                    'text',
                    [
                        'label' => _('Station Web Site URL'),
                        'class' => 'full-width full-height',
                    ]
                ],

                'enable_public_page' => [
                    'radio',
                    [
                        'label' => _('Enable Public Page'),
                        'description' => _('Whether to show or hide the station from public pages and general API results.'),
                        'options' => [0 => _('No'), 1 => _('Yes')],
                        'default' => '1',
                    ]
                ],

                'radio_media_dir' => [
                    'text',
                    [
                        'label' => _('Advanced: Custom Media Directory'),
                        'description' => _('The directory where media files are stored. Leave blank to use default directory.'),
                    ]
                ],
            ],
        ],

        'select_frontend_type' => [
            'legend' => _('Select Broadcasting Service'),

            'elements' => [
                'frontend_type' => [
                    'radio',
                    [
                        'label' => _('Broadcasting Service'),
                        'description' => _('This software delivers your broadcast to the listening audience.'),
                        'options' => $frontend_types,
                        'default' => $frontend_default,
                    ]
                ],
            ],
        ],

        'frontend_local' => [
            'legend' => _('Configure Broadcasting Service'),
            'class' => 'frontend_fieldset',

            'elements' => [

                'port' => [
                    'text',
                    [
                        'label' => _('Advanced: Customize Broadcasting Port'),
                        'description' => _('No other program can be using this port. Leave blank to automatically assign a port.'),
                        'belongsTo' => 'frontend_config',
                    ]
                ],

                'source_pw' => [
                    'text',
                    [
                        'label' => _('Advanced: Customize Source Password'),
                        'description' => _('Leave blank to automatically generate a new password.'),
                        'belongsTo' => 'frontend_config',
                    ]
                ],

                'admin_pw' => [
                    'text',
                    [
                        'label' => _('Advanced: Customize Administrator Password'),
                        'description' => _('Leave blank to automatically generate a new password.'),
                        'belongsTo' => 'frontend_config',
                    ]
                ],

                'max_listeners' => [
                    'text',
                    [
                        'label' => _('Advanced: Maximum Listeners'),
                        'description' => _('Maximum number of total listeners across all streams. Leave blank to use the default (250).'),
                        'belongsTo' => 'frontend_config',
                    ]
                ],

                'custom_config' => [
                    'textarea',
                    [
                        'label' => _('Advanced: Custom Configuration'),
                        'belongsTo' => 'frontend_config',
                        'class' => 'text-preformatted',
                        'description' => _('This code will be included in the frontend configuration. You can use either JSON {"new_key": "new_value"} format or XML &lt;new_key&gt;new_value&lt;/new_key&gt;.'),
                    ]
                ],

            ],
        ],

        'frontend_remote' => [
            'legend' => _('Configure Remote Radio Server'),
            'class' => 'frontend_fieldset',

            'elements' => [

                'remote_type' => [
                    'radio',
                    [
                        'label' => _('Remote Station Type'),
                        'belongsTo' => 'frontend_config',
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
                        'belongsTo' => 'frontend_config',
                    ]
                ],

                'remote_mount' => [
                    'text',
                    [
                        'label' => _('Remote Station Mountpoint/SID'),
                        'description' => _('Specify a mountpoint (i.e. <code>/radio.mp3</code>) or a Shoutcast SID (i.e. <code>2</code>) to specify a specific stream to use.'),
                        'belongsTo' => 'frontend_config',
                    ]
                ],
            ]
        ],

        'select_backend_type' => [
            'legend' => _('Select AutoDJ Service'),

            'elements' => [
                'backend_type' => [
                    'radio',
                    [
                        'label' => _('AutoDJ Service'),
                        'description' => _('This software shuffles from playlists of music constantly and plays when no other radio source is available.'),
                        'options' => $backend_types,
                        'default' => $backend_default,
                    ]
                ],
            ],
        ],

        'backend_liquidsoap' => [
            'legend' => _('Configure LiquidSoap'),
            'class' => 'backend_fieldset',

            'elements' => [

                'enable_streamers' => [
                    'radio',
                    [
                        'label' => _('Allow Streamers / DJs'),
                        'description' => _('If this setting is turned on, streamers (or DJs) will be able to connect directly to your stream and broadcast live music that interrupts the AutoDJ stream.'),
                        'default' => '0',
                        'options' => [0 => _('No'), 1 => _('Yes')],
                    ]
                ],

                'enable_requests' => [
                    'radio',
                    [
                        'label' => _('Allow Song Requests'),
                        'description' => _('Setting this enables listeners to request a song for play on your station. Only songs that are already in your playlists are listed as requestable.'),
                        'default' => '0',
                        'options' => [0 => _('No'), 1 => _('Yes')],
                    ]
                ],

                'request_delay' => [
                    'text',
                    [
                        'label' => _('Request Minimum Delay (Minutes)'),
                        'description' => _('If requests are enabled, this specifies the minimum delay (in minutes) between a request being submitted and being played. If set to zero, no delay is applied.<br><b>Important:</b> Some stream licensing rules require a minimum delay for requests (in the US, this is currently 60 minutes). Check your local regulations for more information.'),
                        'default' => '5',
                    ]
                ],

                'request_threshold' => [
                    'text',
                    [
                        'label' => _('Request Last Played Threshold (Minutes)'),
                        'description' => _('If requests are enabled, this specifies the minimum time (in minutes) between a song playing on the radio and being available to request again. Set to 0 for no threshold.'),
                        'default' => '15',
                    ]
                ],

                'crossfade' => [
                    'text',
                    [
                        'label' => _('Crossfade Duration (Seconds)'),
                        'belongsTo' => 'backend_config',
                        'description' => _('Number of seconds to overlap songs. Set to 0 to disable crossfade completely.'),
                        'default' => 2,
                        'filter' => function($str) {
                            return (int)$str;
                        }
                    ]
                ],

                'custom_config' => [
                    'textarea',
                    [
                        'label' => _('Advanced: Custom Configuration'),
                        'belongsTo' => 'backend_config',
                        'class' => 'text-preformatted',
                        'description' => _('This code will be inserted into your station\'s LiquidSoap configuration, below the playlist configuration and just before the IceCast output. Only use valid LiquidSoap code for this section!'),
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
                        'label' => _('Save Changes'),
                        'class' => 'btn btn-lg btn-primary',
                    ]
                ],
            ],
        ],
    ],
];