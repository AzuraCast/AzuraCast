<?php
$frontends = \Entity\Station::getFrontendAdapters();
$frontend_types = array();
foreach($frontends['adapters'] as $adapter_nickname => $adapter_info)
    $frontend_types[$adapter_nickname] = $adapter_info['name'];
$frontend_default = $frontends['default'];

$backends = \Entity\Station::getBackendAdapters();
$backend_types = array();
foreach($backends['adapters'] as $adapter_nickname => $adapter_info)
    $backend_types[$adapter_nickname] = $adapter_info['name'];
$backend_default = $backends['default'];

return array(   
    'method'        => 'post',
    'enctype'       => 'multipart/form-data',

    'groups' => array(

        'profile' => array(
            'legend' => 'Station Details',
            'elements' => array(

                'name' => array('text', array(
                    'label' => 'Station Name',
                    'class' => 'half-width',
                    'required' => true,
                )),

                'description' => array('textarea', array(
                    'label' => 'Station Description',
                    'class' => 'full-width full-height',
                )),
                
                'frontend_type' => array('radio', array(
                    'label' => 'Station Frontend Type',
                    'description' => 'The type of software you use to deliver your broadcast to the audience.',
                    'options' => $frontend_types,
                    'default' => $frontend_default,
                )),

                'backend_type' => array('radio', array(
                    'label' => 'Station Backend Type',
                    'description' => 'The type of software you use to manage the station\'s playlists and media.',
                    'options' => $backend_types,
                    'default' => $backend_default,
                )),

                'enable_requests' => array('radio', array(
                    'label' => 'Allow Song Requests',
                    'description' => 'Setting this enables listeners to request a song for play on your station. Only songs that are already in your playlists are listed as requestable.',
                    'options' => array(0 => 'No', 1 => 'Yes'),
                )),

                'enable_streamers' => array('radio', array(
                    'label' => 'Allow Streamers / DJs',
                    'description' => 'If this setting is turned on, streamers (or DJs) will be able to connect directly to your stream and broadcast live music that interrupts the AutoDJ stream.',
                    'options' => array(0 => 'No', 1 => 'Yes'),
                )),

            ),
        ),

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

        'submit_grp' => array(
            'elements'      => array(
                'submit'        => array('submit', array(
                    'type'  => 'submit',
                    'label' => 'Save Changes',
                    'class' => 'btn btn-lg btn-primary',
                )),
            ),
        ),
    ),
);