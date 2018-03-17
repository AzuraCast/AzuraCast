<?php
$frontends = \AzuraCast\Radio\Adapters::getFrontendAdapters();
$frontend_types = [];
foreach ($frontends['adapters'] as $adapter_nickname => $adapter_info) {
    $frontend_types[$adapter_nickname] = $adapter_info['name'];
}
$frontend_default = $frontends['default'];

$backends = \AzuraCast\Radio\Adapters::getBackendAdapters();
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
                        'label' => _('Station Web Site'),
                        'class' => 'full-width full-height',
                        'description' => _('Note: This should be the public-facing homepage of the radio station, not the AzuraCast URL. It will be included in broadcast details.'),
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

                'short_name' => [
                    'text',
                    [
                        'label' => _('Advanced: Station URL Stub'),
                        'description' => _('Optionally specify a short URL-friendly name, such as <code>my_station_name</code>, that will be used in this station\'s URLs. Leave this field blank to automatically create one based on the station name.'),
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
                            'shoutcast1' => 'SHOUTcast v1',
                            'shoutcast2' => 'SHOUTcast v2',
                            'icecast' => 'Icecast v2.4+',
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

                'remote_more_markup' => [
                    'markup',
                    [
                        'label' => _('Do More with Remote Servers using the Mount Points feature'),
                        'markup' => _('
                            <div class="well well-sm">
                                Want to connect to more than one stream on the same station, or broadcast to a remote stream from this server?<br>
                                Use the Mount Points feature in your station profile.<br><br>
                                
                                <b>Note:</b> Any mount points you add will override the details specified on this page.
                            </div>
                        '),
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
            'legend' => _('Configure Liquidsoap'),
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
                        'description' => _('This enables listeners to request a song for play on your station. Only songs that are already in your playlists are listed as requestable.'),
                        'default' => '0',
                        'options' => [0 => _('No'), 1 => _('Yes')],
                    ]
                ],

                'charset' => [
                    'radio',
                    [
                        'label' => _('Character Set Encoding'),
                        'description' => _('For most cases, use the default UTF-8 encoding. The older ISO-8859-1 encoding can be used if accepting connections from SHOUTcast 1 DJs or using other legacy software.'),
                        'belongsTo' => 'backend_config',
                        'default' => 'UTF-8',
                        'options' => [
                            'UTF-8' => 'UTF-8',
                            'ISO-8859-1' => 'ISO-8859-1',
                        ],
                    ],
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

                'dj_port' => [
                    'text',
                    [
                        'label' => _('Advanced: Customize DJ/Streamer Port'),
                        'description' => _('No other program can be using this port. Leave blank to automatically assign a port.<br><b>Note:</b> The port after this one (n+1) will automatically be used for legacy connections.'),
                        'belongsTo' => 'backend_config',
                    ]
                ],

                'dj_buffer' => [
                    'text',
                    [
                        'label' => _('Advanced: DJ/Streamer Buffer Time (Seconds)'),
                        'description' => _('The number of seconds of signal to store in case of interruption. Set to the lowest value that your DJs can use without stream interruptions.'),
                        'default' => 5,
                        'belongsTo' => 'backend_config',
                    ]
                ],

                'telnet_port' => [
                    'text',
                    [
                        'label' => _('Advanced: Customize Internal Request Processing Port'),
                        'description' => _('This port is not used by any external process. Only modify this port if the assigned port is in use. Leave blank to automatically assign a port.'),
                        'belongsTo' => 'backend_config',
                    ]
                ],

                'custom_config' => [
                    'textarea',
                    [
                        'label' => _('Advanced: Custom Configuration'),
                        'belongsTo' => 'backend_config',
                        'class' => 'text-preformatted',
                        'description' => _('This code will be inserted into your station\'s Liquidsoap configuration, below the playlist configuration and just before the Icecast output. Only use valid Liquidsoap code for this section!'),
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