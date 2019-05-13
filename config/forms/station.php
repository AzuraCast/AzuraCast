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
            'legend_class' => 'd-none',
            'elements' => [
                'name' => [
                    'text',
                    [
                        'label' => __('Name'),
                        'required' => true,
                        'label_class' => 'mb-2',
                        'form_group_class' => 'col-sm-12 mt-3',
                    ]
                ],

                'description' => [
                    'textarea',
                    [
                        'label' => __('Description'),
                        'label_class' => 'mb-2',
                        'form_group_class' => 'col-sm-12 mt-3',
                    ]
                ],

                'genre' => [
                    'text',
                    [
                        'label' => __('Genre'),
                        'label_class' => 'mb-2',
                        'form_group_class' => 'col-md-6 mt-3',
                    ]
                ],

                'url' => [
                    'text',
                    [
                        'label' => __('Web Site URL'),
                        'description' => __('Note: This should be the public-facing homepage of the radio station, not the AzuraCast URL. It will be included in broadcast details.'),
                        'label_class' => 'mb-2',
                        'form_group_class' => 'col-md-6 mt-3',
                    ]
                ],

                'timezone' => [
                    'select',
                    [
                        'label' => __('Time Zone'),
                        'description' => __('Scheduled playlists and other timed items will be controlled by this time zone.'),
                        'options' => \Azura\Timezone::fetchSelect(),
                        'default' => \App\Customization::DEFAULT_TIMEZONE,
                        'label_class' => 'mb-2',
                        'form_group_class' => 'col-sm-12 mt-3',
                    ]
                ],

                'enable_public_page' => [
                    'toggle',
                    [
                        'label' => __('Enable Public Page'),
                        'description' => __('Show the station in public pages and general API results.'),
                        'selected_text' => __('Yes'),
                        'deselected_text' => __('No'),
                        'default' => true,
                        'form_group_class' => 'col-sm-12 mt-3',
                    ]
                ],

                'short_name' => [
                    'text',
                    [
                        'label' => __('URL Stub'),
                        'label_class' => 'advanced mb-2',
                        'description' => __('Optionally specify a short URL-friendly name, such as <code>my_station_name</code>, that will be used in this station\'s URLs. Leave this field blank to automatically create one based on the station name.'),
                        'form_group_class' => 'col-md-6 mt-3',
                    ]
                ],

                'api_history_items' => [
                    'select',
                    [
                        'label' => __('Number of Recently Played Songs'),
                        'label_class' => 'advanced mb-2',
                        'description' => __('Customize the number of songs that will appear in the "Song History" section for this station and in all public APIs.'),
                        'choices' => [
                            0 => __('Disabled'),
                            1 => '1',
                            5 => '5',
                            10 => '10',
                            15 => '15',
                        ],
                        'default' => Station::DEFAULT_API_HISTORY_ITEMS,
                        'form_group_class' => 'col-md-6 mt-3',
                    ]
                ]
            ],
        ],

        'select_frontend_type' => [
            'legend' => __('Select Broadcasting Service'),
            'legend_class' => 'd-none',
            'class' => 'col-sm-12 mt-3',

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
            'legend_class' => 'd-none',
            'class' => 'frontend_fieldset',

            'elements' => [

                'port' => [
                    'text',
                    [
                        'label' => __('Customize Broadcasting Port'),
                        'label_class' => 'advanced mb-2',
                        'description' => __('No other program can be using this port. Leave blank to automatically assign a port.'),
                        'belongsTo' => 'frontend_config',
                        'form_group_class' => 'col-md-6 mt-3',
                    ]
                ],

                'max_listeners' => [
                    'text',
                    [
                        'label' => __('Maximum Listeners'),
                        'label_class' => 'advanced mb-2',
                        'description' => __('Maximum number of total listeners across all streams. Leave blank to use the default (250).'),
                        'belongsTo' => 'frontend_config',
                        'form_group_class' => 'col-md-6 mt-3',
                    ]
                ],

                'source_pw' => [
                    'text',
                    [
                        'label' => __('Customize Source Password'),
                        'label_class' => 'advanced mb-2',
                        'description' => __('Leave blank to automatically generate a new password.'),
                        'belongsTo' => 'frontend_config',
                        'form_group_class' => 'col-md-6 mt-3',
                    ]
                ],

                'admin_pw' => [
                    'text',
                    [
                        'label' => __('Customize Administrator Password'),
                        'label_class' => 'advanced mb-2',
                        'description' => __('Leave blank to automatically generate a new password.'),
                        'belongsTo' => 'frontend_config',
                        'form_group_class' => 'col-md-6 mt-3',
                    ]
                ],

                'custom_config' => [
                    'textarea',
                    [
                        'label' => __('Custom Configuration'),
                        'label_class' => 'advanced mb-2',
                        'belongsTo' => 'frontend_config',
                        'class' => 'text-preformatted',
                        'description' => __('This code will be included in the frontend configuration. You can use either JSON {"new_key": "new_value"} format or XML &lt;new_key&gt;new_value&lt;/new_key&gt;.'),
                        'form_group_class' => 'col-sm-12 mt-3',
                    ]
                ],

            ],
        ],

        'select_backend_type' => [
            'legend' => __('Select AutoDJ Service'),
            'legend_class' => 'd-none',

            'elements' => [
                'backend_type' => [
                    'radio',
                    [
                        'label' => __('AutoDJ Service'),
                        'description' => __('This software shuffles from playlists of music constantly and plays when no other radio source is available.'),
                        'options' => $backend_types,
                        'default' => Adapters::DEFAULT_BACKEND,
                        'form_group_class' => 'col-sm-12 mt-3',
                    ]
                ],
            ],
        ],

        'backend_liquidsoap' => [
            'legend' => __('Configure Liquidsoap'),
            'legend_class' => 'd-none',
            'class' => 'backend_fieldset',

            'elements' => [

                'crossfade_type' => [
                    'radio',
                    [
                        'label' => __('Crossfade Method'),
                        'belongsTo' => 'backend_config',
                        'description' => __('Choose a method to use when transitioning from one song to another. Smart Mode considers the volume of the two tracks when fading for a smoother effect, but requires more CPU resources.'),
                        'choices' => [
                            \App\Radio\Backend\Liquidsoap::CROSSFADE_SMART => __('Smart Mode'),
                            \App\Radio\Backend\Liquidsoap::CROSSFADE_NORMAL => __('Normal Mode'),
                            \App\Radio\Backend\Liquidsoap::CROSSFADE_DISABLED => __('Disable Crossfading')
                        ],
                        'default' => \App\Radio\Backend\Liquidsoap::CROSSFADE_NORMAL,
                        'form_group_class' => 'col-md-8 mt-3',
                    ]
                ],

                'crossfade' => [
                    'number',
                    [
                        'label' => __('Crossfade Duration (Seconds)'),
                        'belongsTo' => 'backend_config',
                        'description' => __('Number of seconds to overlap songs.'),
                        'default' => 2,
                        'min' => '0.0',
                        'max' => '30.0',
                        'step' => '0.1',
                        'label_class' => 'mb-2',
                        'form_group_class' => 'col-md-4 mt-3',
                    ]
                ],

                'nrj' => [
                    'toggle',
                    [
                        'label' => __('Apply Compression and Normalization'),
                        'belongsTo' => 'backend_config',
                        'description' => __('Compress and normalize your station\'s audio, producing a more uniform and "full" sound.'),
                        'selected_text' => __('Yes'),
                        'deselected_text' => __('No'),
                        'default' => false,
                        'form_group_class' => 'col-sm-12 mt-3',
                    ]
                ],

                'enable_requests' => [
                    'toggle',
                    [
                        'label' => __('Allow Song Requests'),
                        'description' => __('Enable listeners to request a song for play on your station. Only songs that are already in your playlists are requestable.'),
                        'selected_text' => __('Yes'),
                        'deselected_text' => __('No'),
                        'default' => false,
                        'form_group_class' => 'col-sm-12 mt-3',
                    ]
                ],

                'request_delay' => [
                    'number',
                    [
                        'label' => __('Request Minimum Delay (Minutes)'),
                        'description' => __('If requests are enabled, this specifies the minimum delay (in minutes) between a request being submitted and being played. If set to zero, no delay is applied.<br><b>Important:</b> Some stream licensing rules require a minimum delay for requests (in the US, this is currently 60 minutes). Check your local regulations for more information.'),
                        'default' => Station::DEFAULT_REQUEST_DELAY,
                        'min' => '0',
                        'max' => '1440',
                        'label_class' => 'mb-2',
                        'form_group_class' => 'col-md-6 mt-3',
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
                        'label_class' => 'mb-2',
                        'form_group_class' => 'col-md-6 mt-3',
                    ]
                ],

                'enable_streamers' => [
                    'toggle',
                    [
                        'label' => __('Allow Streamers / DJs'),
                        'description' => __('If enabled, streamers (or DJs) will be able to connect directly to your stream and broadcast live music that interrupts the AutoDJ stream.'),
                        'selected_text' => __('Yes'),
                        'deselected_text' => __('No'),
                        'default' => false,
                        'form_group_class' => 'col-md-8 mt-3',
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
                        'form_group_class' => 'col-md-4 mt-3',
                    ]
                ],

                'dj_port' => [
                    'text',
                    [
                        'label' => __('Customize DJ/Streamer Port'),
                        'label_class' => 'advanced mb-2',
                        'description' => __('No other program can be using this port. Leave blank to automatically assign a port.<br><b>Note:</b> The port after this one (n+1) will automatically be used for legacy connections.'),
                        'belongsTo' => 'backend_config',
                        'form_group_class' => 'col-md-6 mt-3',
                    ]
                ],

                'telnet_port' => [
                    'text',
                    [
                        'label' => __('Customize Internal Request Processing Port'),
                        'label_class' => 'advanced mb-2',
                        'description' => __('This port is not used by any external process. Only modify this port if the assigned port is in use. Leave blank to automatically assign a port.'),
                        'belongsTo' => 'backend_config',
                        'form_group_class' => 'col-md-6 mt-3',
                    ]
                ],

                'dj_buffer' => [
                    'number',
                    [
                        'label' => __('DJ/Streamer Buffer Time (Seconds)'),
                        'label_class' => 'advanced mb-2',
                        'description' => __('The number of seconds of signal to store in case of interruption. Set to the lowest value that your DJs can use without stream interruptions.'),
                        'default' => 5,
                        'min' => 0,
                        'max' => 60,
                        'step' => 1,
                        'belongsTo' => 'backend_config',
                        'form_group_class' => 'col-md-6 mt-3',
                    ]
                ],

                'dj_mount_point' => [
                    'text',
                    [
                        'label' => __('Customize DJ/Streamer Mount Point'),
                        'label_class' => 'advanced mb-2',
                        'description' => __('If your streaming software requires a specific mount point path, specify it here. Otherwise, use the default.'),
                        'belongsTo' => 'backend_config',
                        'default' => '/',
                        'form_group_class' => 'col-md-6 mt-3',
                    ],
                ],

                'enable_replaygain_metadata' => [
                    'toggle',
                    [
                        'label' => __('Use Replaygain Metadata'),
                        'label_class' => 'advanced',
                        'belongsTo' => 'backend_config',
                        'description' => __('Instruct Liquidsoap to use any replaygain metadata associated with a song to control its volume level.'),
                        'selected_text' => __('Yes'),
                        'deselected_text' => __('No'),
                        'default' => false,
                        'form_group_class' => 'col-md-6 mt-3',
                    ]
                ],

                'charset' => [
                    'radio',
                    [
                        'label' => __('Character Set Encoding'),
                        'label_class' => 'advanced',
                        'description' => __('For most cases, use the default UTF-8 encoding. The older ISO-8859-1 encoding can be used if accepting connections from SHOUTcast 1 DJs or using other legacy software.'),
                        'belongsTo' => 'backend_config',
                        'default' => 'UTF-8',
                        'choices' => [
                            'UTF-8' => 'UTF-8',
                            'ISO-8859-1' => 'ISO-8859-1',
                        ],
                        'form_group_class' => 'col-md-6 mt-3',
                    ],
                ],

                'custom_config' => [
                    'textarea',
                    [
                        'label' => __('Custom Configuration'),
                        'label_class' => 'advanced mb-2',
                        'belongsTo' => 'backend_config',
                        'class' => 'text-preformatted',
                        'description' => __('This code will be inserted into your station\'s Liquidsoap configuration, below the playlist configuration and just before the Icecast output. Only use valid Liquidsoap code for this section!'),
                        'form_group_class' => 'col-sm-12 mt-3',
                    ]
                ],

            ],
        ],

        'admin' => [
            'legend' => __('Administration'),
            'legend_class' => 'd-none',

            'elements' => [

                'is_enabled' => [
                    'toggle',
                    [
                        'label' => __('Enable Broadcasting'),
                        'description' => __('If disabled, the station will not broadcast or shuffle its AutoDJ.'),
                        'selected_text' => __('Yes'),
                        'deselected_text' => __('No'),
                        'default' => true,
                        'form_group_class' => 'col-md-6 mt-3',
                    ]
                ],

                'storage_quota' => [
                    'text',
                    [
                        'label' => __('Storage Quota'),
                        'description' => __('Set a maximum disk space that this station can use. Specify the size with unit, i.e. "8 GB". Units are measured in 1024 bytes. Leave blank to default to the available space on the disk.'),
                        'label_class' => 'mb-2',
                        'form_group_class' => 'col-md-6 mt-3',
                    ]
                ],

                'radio_base_dir' => [
                    'text',
                    [
                        'label' => __('Base Station Directory'),
                        'label_class' => 'advanced mb-2',
                        'description' => __('The parent directory where station playlist and configuration files are stored. Leave blank to use default directory.'),
                        'form_group_class' => 'col-md-6 mt-3',
                    ]
                ],

                'radio_media_dir' => [
                    'text',
                    [
                        'label' => __('Custom Media Directory'),
                        'label_class' => 'advanced mb-2',
                        'description' => __('The directory where media files are stored. Leave blank to use default directory.'),
                        'form_group_class' => 'col-md-6 mt-3',
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
                        'form_group_class' => 'col-sm-12 mt-3',
                    ]
                ],
            ],
        ],
    ],
];
