<?php
$frontends = \Entity\Station::getFrontendAdapters();
$frontend_types = [];
foreach ($frontends['adapters'] as $adapter_nickname => $adapter_info)
    $frontend_types[$adapter_nickname] = $adapter_info['name'];
$frontend_default = $frontends['default'];

$backends = \Entity\Station::getBackendAdapters();
$backend_types = [];
foreach ($backends['adapters'] as $adapter_nickname => $adapter_info)
    $backend_types[$adapter_nickname] = $adapter_info['name'];
$backend_default = $backends['default'];

return [
    'method' => 'post',
    'enctype' => 'multipart/form-data',

    'groups' => [

        'profile' => [
            'legend' => _('Station Details'),
            'elements' => [

                'name' => ['text', [
                    'label' => _('Station Name'),
                    'class' => 'half-width',
                    'required' => true,
                ]],

                'description' => ['textarea', [
                    'label' => _('Station Description'),
                    'class' => 'full-width full-height',
                ]],

                'frontend_type' => ['radio', [
                    'label' => _('Station Frontend Type'),
                    'description' => _('The type of software you use to deliver your broadcast to the audience.'),
                    'options' => $frontend_types,
                    'default' => $frontend_default,
                ]],

                'backend_type' => ['radio', [
                    'label' => _('Station Backend Type'),
                    'description' => _('The type of software you use to manage the station\'s playlists and media.'),
                    'options' => $backend_types,
                    'default' => $backend_default,
                ]],

                'enable_requests' => ['radio', [
                    'label' => _('Allow Song Requests'),
                    'description' => _('Setting this enables listeners to request a song for play on your station. Only songs that are already in your playlists are listed as requestable.'),
                    'default' => '0',
                    'options' => [0 => 'No', 1 => 'Yes'],
                ]],

                'request_delay' => ['text', [
                    'label' => _('Request Minimum Delay (Minutes)'),
                    'description' => _('If requests are enabled, this specifies the minimum delay (in minutes) between a request being submitted and being played. If set to zero, no delay is applied.<br><b>Important:</b> Some stream licensing rules require a minimum delay for requests (in the US, this is currently 60 minutes). Check your local regulations for more information.'),
                    'default' => '5',
                ]],

                'enable_streamers' => ['radio', [
                    'label' => _('Allow Streamers / DJs'),
                    'description' => _('If this setting is turned on, streamers (or DJs) will be able to connect directly to your stream and broadcast live music that interrupts the AutoDJ stream.'),
                    'default' => '0',
                    'options' => [0 => 'No', 1 => 'Yes'],
                ]],

            ],
        ],

        'frontend_icecast' => [
            'legend' => _('Advanced Settings: IceCast 2'),
            'class'  => 'frontend_fieldset',
            'description' => _('These settings are intended for advanced users only. You can safely leave all of these options blank and sensible defaults will be used for them.'),

            'elements' => [

                'port' => ['text', [
                    'label' => _('Broadcasting Port'),
                    'description' => _('No other program can be using this port. An available port is automatically assigned to each new station.'),
                    'belongsTo' => 'frontend_config',
                ]],

                'source_pw' => ['text', [
                    'label' => _('Source Password'),
                    'belongsTo' => 'frontend_config',
                ]],

                'admin_pw' => ['text', [
                    'label' => _('Admin Password'),
                    'belongsTo' => 'frontend_config',
                ]],

            ],
        ],

        'backend_liquidsoap' => [
            'legend' => _('Advanced Settings: LiquidSoap'),
            'class'  => 'backend_fieldset',
            'description' => _('These settings are intended for advanced users only. You can safely leave all of these options blank and sensible defaults will be used for them.'),

            'elements' => [

                'custom_config' => ['textarea', [
                    'label' => _('Custom Configuration'),
                    'belongsTo' => 'backend_config',
                    'description' => _('This code will be inserted into your station\'s LiquidSoap configuration, below the playlist configuration and just before the IceCast output. Only use valid LiquidSoap code for this section!'),
                ]],

            ],
        ],

        'submit_grp' => [
            'elements' => [
                'submit' => ['submit', [
                    'type' => 'submit',
                    'label' => _('Save Changes'),
                    'class' => 'btn btn-lg btn-primary',
                ]],
            ],
        ],
    ],
];