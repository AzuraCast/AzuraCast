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

        'profile' => [
            'legend' => _('Station Details'),
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

                'url' => [
                    'text',
                    [
                        'label' => _('Station Web Site URL'),
                        'class' => 'full-width full-height',
                    ]
                ],

            ],
        ],

        'advanced' => [
            'legend' => _('Advanced Configuration'),
            'elements' => [

                'frontend_type' => [
                    'radio',
                    [
                        'label' => _('Station Frontend Type'),
                        'description' => _('The type of software you use to deliver your broadcast to the audience.'),
                        'options' => $frontend_types,
                        'default' => $frontend_default,
                    ]
                ],

                'backend_type' => [
                    'radio',
                    [
                        'label' => _('Station Backend Type'),
                        'description' => _('The type of software you use to manage the station\'s playlists and media.'),
                        'options' => $backend_types,
                        'default' => $backend_default,
                    ]
                ],

                'radio_media_dir' => [
                    'text',
                    [
                        'label' => _('Station Media Directory'),
                        'description' => _('The directory where media files are stored. Leave blank to use default directory.'),
                    ]
                ],

            ],
        ],

        'frontend_local' => [
            'legend' => _('Configure Radio Broadcasting'),
            'class' => 'frontend_fieldset',

            'elements' => [

                'enable_streamers' => [
                    'radio',
                    [
                        'label' => _('Allow Streamers / DJs'),
                        'description' => _('If this setting is turned on, streamers (or DJs) will be able to connect directly to your stream and broadcast live music that interrupts the AutoDJ stream.'),
                        'default' => '0',
                        'options' => [0 => 'No', 1 => 'Yes'],
                    ]
                ],

                'port' => [
                    'text',
                    [
                        'label' => _('Broadcasting Port'),
                        'description' => _('No other program can be using this port. Leave blank to automatically assign a port.'),
                        'belongsTo' => 'frontend_config',
                    ]
                ],

                'source_pw' => [
                    'text',
                    [
                        'label' => _('Source Password'),
                        'description' => _('Leave blank to automatically generate a new password.'),
                        'belongsTo' => 'frontend_config',
                    ]
                ],

                'admin_pw' => [
                    'text',
                    [
                        'label' => _('Admin Password'),
                        'description' => _('Leave blank to automatically generate a new password.'),
                        'belongsTo' => 'frontend_config',
                    ]
                ],

                'custom_config' => [
                    'textarea',
                    [
                        'label' => _('Custom Configuration'),
                        'belongsTo' => 'frontend_config',
                        'description' => _('This code will be included in the frontend configuration. You can use either JSON {"new_key": "new_value"} format or XML &lt;new_key&gt;new_value&lt;/new_key&gt;.'),
                    ]
                ],

            ],
        ],

        'frontend_remote' => [
            'legend' => _('Configure External Radio Server'),
            'class' => 'frontend_fieldset',

            'elements' => [

                'remote_type' => [
                    'radio',
                    [
                        'label' => _('Radio Station Type'),
                        'belongsTo' => 'frontend_config',
                        'options' => [
                            'shoutcast1' => _('ShoutCast v1'),
                            'shoutcast2' => _('ShoutCast v2'),
                            'icecast' => _('IceCast v2.4+'),
                        ],
                    ]
                ],

                'remote_url' => [
                    'text',
                    [
                        'label' => _('Radio Station Base URL'),
                        'belongsTo' => 'frontend_config',
                    ]
                ],

            ]
        ],

        'backend_liquidsoap' => [
            'legend' => _('Configure LiquidSoap'),
            'class' => 'backend_fieldset',

            'elements' => [

                'enable_requests' => [
                    'radio',
                    [
                        'label' => _('Allow Song Requests'),
                        'description' => _('Setting this enables listeners to request a song for play on your station. Only songs that are already in your playlists are listed as requestable.'),
                        'default' => '0',
                        'options' => [0 => 'No', 1 => 'Yes'],
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