<?php
$cat_raw = \Entity\Station::getCategories();
$cat_select = array();

foreach($cat_raw as $cat_id => $cat_info)
    $cat_select[$cat_id] = $cat_info['name'];

return array(   
    'method'        => 'post',
    'enctype'       => 'multipart/form-data',

    'elements'      => array(
                
        'name' => array('text', array(
            'label' => 'Station Name',
            'class' => 'half-width',
            'required' => true,
        )),

        'is_active' => array('radio', array(
            'label' => 'Is Active',
            'description' => 'Is visible in the PVL network player interface.',
            'multiOptions' => array(
                1 => 'Yes',
                0 => 'No',
            ),
            'default' => 1,
        )),

        'is_special' => array('radio', array(
            'label' => 'Is Special Station',
            'description' => 'If station is classified as special, it will remain in the system even if inactive.',
            'multiOptions' => array(
                1 => 'Yes',
                0 => 'No',
            ),
            'default' => 0,
        )),

        'hide_if_inactive' => array('radio', array(
            'label' => 'Hide if Inactive',
            'description' => 'Remove station from the display list if it is currently offline.',
            'multiOptions' => array(
                1 => 'Yes',
                0 => 'No',
            ),
            'default' => 0,
        )),
        
        'genre' => array('text', array(
            'label' => 'Station Genre',
            'description' => 'Listed underneath the station in the player.',
        )),

        'description' => array('textarea', array(
            'label' => 'Description',
            'class' => 'full-width half-height',
        )),

        'owner' => array('text', array(
            'label' => 'Station Owner',
            'class' => 'half-width',
        )),

        'category' => array('radio', array(
            'label' => 'Station Category',
            'multiOptions' => $cat_select,
            'required' => true,
        )),

        'type' => array('radio', array(
            'label' => 'Media Type',
            'multiOptions' => array(
                'shoutcast1'        => 'ShoutCast v1',
                'shoutcast2'        => 'ShoutCast v2',
                'icecast'           => 'IceCast',
                'stream'            => 'Popout Stream Player',
                /*
                'swf'               => 'SWF Embed',
                'video'             => 'Video Stream (Flash/RTMP)',
                'iframe'            => 'IFRAME Content',
                'link'              => 'URL Link',
                */
            ),
            'required' => true,
        )),

        'weight' => array('text', array(
            'label' => 'Sort Order',
            'description' => 'Lower numbers appear higher on the list of stations.',
        )),

        'image_url' => array('file', array(
            'label' => 'Upload New Icon',
            'description' => 'To replace the existing icon associated with this station, upload a new one using the file browser below. Icons should be 150x150px in dimension.',
        )),

        'web_url' => array('text', array(
            'label' => 'Web URL',
            'description' => 'Include full address (with http://).',
            'class' => 'half-width',
        )),

        'nowplaying_url' => array('text', array(
            'label' => 'Now Playing Feed URL',
            'description' => 'Include full address (with http://).',
            'class' => 'half-width',
        )),

        'stream_url' => array('text', array(
            'label' => 'Stream URL',
            'description' => 'Include full address (with http://). For ShoutCast streams, append ;stream.nsv to the end.',
            'class' => 'half-width',
        )),

        'stream_alternate' => array('textarea', array(
            'label' => 'Stream Alternates',
            'description' => 'Enter each stream on a new line, with a pipe between the name and the URL, i.e. Mobile|http://www.url.com/',
            'class' => 'half-width half-height',
        )),

        'requests_enabled' => array('radio', array(
            'label' => 'Enable Request System',
            'description' => 'Enable the "Submit Request" button under this station.',
            'multiOptions' => array(
                1 => 'Yes',
                0 => 'No',
            ),
            'default' => 0,
        )),

        'requests_ccast_username' => array('text', array(
            'label' => 'Request System CentovaCast Account Name',
            'description' => 'Account username in the CentovaCast system, if requests are enabled.',
            'class' => 'half-width',
        )),

        'requests_external_url' => array('text', array(
            'label' => 'External URL for Third-Party Request System',
            'description' => 'If the station is using a non-CentovaCast request system, enter the URL for it below.',
            'class' => 'half-width',
        )),

        'twitter_url' => array('text', array(
            'label' => 'Twitter URL',
            'description' => 'Include full address of the station\'s Twitter account (with http://).',
            'class' => 'half-width',
        )),

        'gcal_url' => array('text', array(
            'label' => 'Google Calendar XML Feed URL',
            'description' => 'Include full address of the feed (ending in /basic or /full) (with http://).',
            'class' => 'half-width',
        )),

        'irc' => array('text', array(
            'label' => 'IRC Channel Name',
            'description' => 'Include hash tag: #channelname',
        )),

        'admin_notes' => array('textarea', array(
            'label' => 'Administration Notes',
            'description' => 'These notes are only visible by network administration.',
            'class' => 'full-width half-height',
        )),

        'station_notes' => array('textarea', array(
            'label' => 'Station Notes',
            'description' => 'These notes are visible/editable by station owners.',
            'class' => 'full-width half-height',
        )),
        
        'submit'        => array('submit', array(
            'type'  => 'submit',
            'label' => 'Save Changes',
            'helper' => 'formButton',
            'class' => 'ui-button',
        )),
    ),
);