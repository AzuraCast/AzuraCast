<?php
use App\Entity\Station;
use App\Entity\StationMountInterface;
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

    'tabs' => [
        'profile'   => __('Station Profile'),
        'frontend'  => __('Broadcasting'),
        'backend'   => __('AutoDJ'),
        'admin'     => __('Administration'),
    ],

    'groups' => [
        'profile' => [
            'tab' => 'profile',
            'use_grid' => true,

            'elements' => [
                'name' => [
                    'text',
                    [
                        'label' => __('Name'),
                        'required' => true,
                        'form_group_class' => 'col-sm-12',
                    ]
                ],

                'description' => [
                    'textarea',
                    [
                        'label' => __('Description'),
                        'form_group_class' => 'col-sm-12',
                    ]
                ],

                'genre' => [
                    'text',
                    [
                        'label' => __('Genre'),
                        'form_group_class' => 'col-md-6',
                    ]
                ],

                'url' => [
                    'text',
                    [
                        'label' => __('Web Site URL'),
                        'description' => __('Note: This should be the public-facing homepage of the radio station, not the AzuraCast URL. It will be included in broadcast details.'),
                        'form_group_class' => 'col-md-6',
                    ]
                ],

                'timezone' => [
                    'select',
                    [
                        'label' => __('Time Zone'),
                        'description' => __('Scheduled playlists and other timed items will be controlled by this time zone.'),
                        'options' => \App\Timezone::fetchSelect(),
                        'default' => \App\Customization::DEFAULT_TIMEZONE,
                        'form_group_class' => 'col-sm-12',
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
                        'form_group_class' => 'col-sm-12',
                    ]
                ],

                'short_name' => [
                    'text',
                    [
                        'label' => __('URL Stub'),
                        'label_class' => 'advanced',
                        'description' => __('Optionally specify a short URL-friendly name, such as <code>my_station_name</code>, that will be used in this station\'s URLs. Leave this field blank to automatically create one based on the station name.'),
                        'form_group_class' => 'col-md-6',
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
                        'form_group_class' => 'col-md-6',
                    ]
                ]
            ],
        ],

        'select_frontend_type' => [
            'tab' => 'frontend',

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
            'use_grid' => true,
            'class' => 'frontend_fieldset',
            'tab' => 'frontend',

            'elements' => [

                'port' => [
                    'text',
                    [
                        'label' => __('Customize Broadcasting Port'),
                        'label_class' => 'advanced',
                        'description' => __('No other program can be using this port. Leave blank to automatically assign a port.'),
                        'belongsTo' => 'frontend_config',
                        'form_group_class' => 'col-md-6',
                    ]
                ],

                'max_listeners' => [
                    'text',
                    [
                        'label' => __('Maximum Listeners'),
                        'label_class' => 'advanced',
                        'description' => __('Maximum number of total listeners across all streams. Leave blank to use the default (250).'),
                        'belongsTo' => 'frontend_config',
                        'form_group_class' => 'col-md-6',
                    ]
                ],

                'source_pw' => [
                    'text',
                    [
                        'label' => __('Customize Source Password'),
                        'label_class' => 'advanced',
                        'description' => __('Leave blank to automatically generate a new password.'),
                        'belongsTo' => 'frontend_config',
                        'form_group_class' => 'col-md-6',
                    ]
                ],

                'admin_pw' => [
                    'text',
                    [
                        'label' => __('Customize Administrator Password'),
                        'label_class' => 'advanced',
                        'description' => __('Leave blank to automatically generate a new password.'),
                        'belongsTo' => 'frontend_config',
                        'form_group_class' => 'col-md-6',
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
                        'form_group_class' => 'col-sm-12',
                    ]
                ],

            ],
        ],

        'select_backend_type' => [
            'tab' => 'backend',
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
            'use_grid' => true,
            'class' => 'backend_fieldset',
            'tab' => 'backend',

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
                        'form_group_class' => 'col-md-8',
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
                        'form_group_class' => 'col-md-4',
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
                        'form_group_class' => 'col-sm-12',
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
                        'form_group_class' => 'col-sm-12',
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
                        'form_group_class' => 'col-md-6',
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
                        'form_group_class' => 'col-md-6',
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
                        'form_group_class' => 'col-md-12',
                    ]
                ],

                'record_streams' => [
                    'toggle',
                    [
                        'label' => __('Record Live Broadcasts'),
                        'description' => __('If enabled, AzuraCast will automatically record any live broadcasts made to this station to per-broadcast recordings.'),
                        'selected_text' => __('Yes'),
                        'deselected_text' => __('No'),
                        'default' => false,
                        'belongsTo' => 'backend_config',
                        'form_group_class' => 'col-md-4',
                    ]
                ],

                'record_streams_format' => [
                    'radio',
                    [
                        'label' => __('Live Broadcast Recording Format'),
                        'choices' => [
                            StationMountInterface::FORMAT_MP3 => 'MP3',
                            StationMountInterface::FORMAT_OGG => 'OGG Vorbis',
                            StationMountInterface::FORMAT_OPUS => 'OGG Opus',
                            StationMountInterface::FORMAT_AAC => 'AAC+ (MPEG4 HE-AAC v2)',
                        ],
                        'belongsTo' => 'backend_config',
                        'form_group_class' => 'col-md-4',
                    ]
                ],

                'record_streams_bitrate' => [
                    'radio',
                    [
                        'label' => __('Live Broadcast Recording Bitrate (kbps)'),
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
                        'belongsTo' => 'backend_config',
                        'form_group_class' => 'col-md-4',
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
                        'form_group_class' => 'col-md-4',
                    ]
                ],

                'dj_port' => [
                    'text',
                    [
                        'label' => __('Customize DJ/Streamer Port'),
                        'label_class' => 'advanced',
                        'description' => __('No other program can be using this port. Leave blank to automatically assign a port.<br><b>Note:</b> The port after this one (n+1) will automatically be used for legacy connections.'),
                        'belongsTo' => 'backend_config',
                        'form_group_class' => 'col-md-6',
                    ]
                ],

                'telnet_port' => [
                    'text',
                    [
                        'label' => __('Customize Internal Request Processing Port'),
                        'label_class' => 'advanced',
                        'description' => __('This port is not used by any external process. Only modify this port if the assigned port is in use. Leave blank to automatically assign a port.'),
                        'belongsTo' => 'backend_config',
                        'form_group_class' => 'col-md-6',
                    ]
                ],

                'dj_buffer' => [
                    'number',
                    [
                        'label' => __('DJ/Streamer Buffer Time (Seconds)'),
                        'label_class' => 'advanced',
                        'description' => __('The number of seconds of signal to store in case of interruption. Set to the lowest value that your DJs can use without stream interruptions.'),
                        'default' => 5,
                        'min' => 0,
                        'max' => 60,
                        'step' => 1,
                        'belongsTo' => 'backend_config',
                        'form_group_class' => 'col-md-6',
                    ]
                ],

                'dj_mount_point' => [
                    'text',
                    [
                        'label' => __('Customize DJ/Streamer Mount Point'),
                        'label_class' => 'advanced',
                        'description' => __('If your streaming software requires a specific mount point path, specify it here. Otherwise, use the default.'),
                        'belongsTo' => 'backend_config',
                        'default' => '/',
                        'form_group_class' => 'col-md-6',
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
                        'form_group_class' => 'col-md-6',
                    ]
                ],

                'use_manual_autodj' => [
                    'toggle',
                    [
                        'label' => __('Manual AutoDJ Mode'),
                        'label_class' => 'advanced',
                        'description' => __('This mode disables AzuraCast\'s AutoDJ management, using Liquidsoap itself to manage song playback. "Next Song" and some other features will not be available.'),
                        'selected_text' => __('Yes'),
                        'deselected_text' => __('No'),
                        'default' => false,
                        'belongsTo' => 'backend_config',
                        'form_group_class' => 'col-md-6',
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
                        'form_group_class' => 'col-md-6',
                    ],
                ],

                'custom_config' => [
                    'textarea',
                    [
                        'label' => __('Custom Configuration'),
                        'label_class' => 'advanced',
                        'belongsTo' => 'backend_config',
                        'class' => 'text-preformatted',
                        'description' => __('This code will be inserted into your station\'s Liquidsoap configuration, below the playlist configuration and just before the Icecast output. Only use valid Liquidsoap code for this section!'),
                        'form_group_class' => 'col-sm-12',
                    ]
                ],

            ],
        ],

        'admin' => [
            'use_grid' => true,
            'tab' => 'admin',

            'elements' => [

                'is_enabled' => [
                    'toggle',
                    [
                        'label' => __('Enable Broadcasting'),
                        'description' => __('If disabled, the station will not broadcast or shuffle its AutoDJ.'),
                        'selected_text' => __('Yes'),
                        'deselected_text' => __('No'),
                        'default' => true,
                        'form_group_class' => 'col-md-6',
                    ]
                ],

                'storage_quota' => [
                    'text',
                    [
                        'label' => __('Storage Quota'),
                        'description' => __('Set a maximum disk space that this station can use. Specify the size with unit, i.e. "8 GB". Units are measured in 1024 bytes. Leave blank to default to the available space on the disk.'),
                        'form_group_class' => 'col-md-6 ',
                    ]
                ],

                'radio_base_dir' => [
                    'text',
                    [
                        'label' => __('Base Station Directory'),
                        'label_class' => 'advanced',
                        'description' => __('The parent directory where station playlist and configuration files are stored. Leave blank to use default directory.'),
                        'form_group_class' => 'col-md-6',
                    ]
                ],

                'radio_media_dir' => [
                    'text',
                    [
                        'label' => __('Custom Media Directory'),
                        'label_class' => 'advanced',
                        'description' => __('The directory where media files are stored. Leave blank to use default directory.'),
                        'form_group_class' => 'col-md-6',
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
