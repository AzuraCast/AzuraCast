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
            'legend' => 'Station Details',
            'elements' => [

                'name' => ['text', [
                    'label' => 'Station Name',
                    'class' => 'half-width',
                    'required' => true,
                ]],

                'description' => ['textarea', [
                    'label' => 'Station Description',
                    'class' => 'full-width full-height',
                ]],

                'frontend_type' => ['radio', [
                    'label' => 'Station Frontend Type',
                    'description' => 'The type of software you use to deliver your broadcast to the audience.',
                    'options' => $frontend_types,
                    'default' => $frontend_default,
                ]],

                'backend_type' => ['radio', [
                    'label' => 'Station Backend Type',
                    'description' => 'The type of software you use to manage the station\'s playlists and media.',
                    'options' => $backend_types,
                    'default' => $backend_default,
                ]],

                'enable_requests' => ['radio', [
                    'label' => 'Allow Song Requests',
                    'description' => 'Setting this enables listeners to request a song for play on your station. Only songs that are already in your playlists are listed as requestable.',
                    'default' => '0',
                    'options' => [0 => 'No', 1 => 'Yes'],
                ]],

                'request_delay' => ['text', [
                    'label' => 'Request Minimum Delay (Minutes)',
                    'description' => 'If requests are enabled, this specifies the minimum delay (in minutes) between a request being submitted and being played. If set to zero, no delay is applied.<br><b>Important:</b> Some stream licensing rules require a minimum delay for requests (in the US, this is currently 60 minutes). Check your local regulations for more information.',
                    'default' => '5',
                ]],

                'enable_streamers' => ['radio', [
                    'label' => 'Allow Streamers / DJs',
                    'description' => 'If this setting is turned on, streamers (or DJs) will be able to connect directly to your stream and broadcast live music that interrupts the AutoDJ stream.',
                    'default' => '0',
                    'options' => [0 => 'No', 1 => 'Yes'],
                ]],

            ],
        ],

        /*
        'admin' => array(
            'legend' => 'Administrator Settings',
            'elements' => array(

                'radio_port' => array('text', array(
                    'label' => 'Radio Frontend Broadcast Port',
                )),

                'radio_source_pw' => array('text', array(
                    'label' => 'Radio Source Password',
                )),

                'radio_admin_pw' => array('text', array(
                    'label' => 'Radio Administrator Password',
                )),

                'radio_base_dir' => array('text', array(
                    'label' => 'Radio Base Path',
                )),

            ),
        ),
        */

        'submit_grp' => [
            'elements' => [
                'submit' => ['submit', [
                    'type' => 'submit',
                    'label' => 'Save Changes',
                    'class' => 'btn btn-lg btn-primary',
                ]],
            ],
        ],
    ],
];