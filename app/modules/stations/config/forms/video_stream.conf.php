<?php
return array(
    'method'        => 'post',
    'enctype'       => 'multipart/form-data',

    'elements' => array(

        'name' => array('text', array(
            'label' => 'Stream Name',
            'class' => 'half-width',
            'required' => true,

            'description' => 'This should describe what distinguishes this stream from your other streams. Good examples: "Music Only", or "Mobile 64kbps"',
        )),

        'is_active' => array('radio', array(
            'label' => 'Is Stream Active',
            'multiOptions' => array(0 => 'No', 1 => 'Yes'),
            'default' => 1,

            'description' => 'Mark this stream as inactive to remove it from public displays and halt all Now Playing processing, but leave it in the database for potential later use.',
            'required' => true,
        )),

        'type' => array('hidden', array('value' => 'video')),

        'stream_url' => array('text', array(
            'label' => 'Stream Broadcast URL',
            'class' => 'half-width',

            'description' => 'The address (including http[s]://) where listeners can tune in to your radio station or video stream.',
            'required' => true,
        )),

        'nowplaying_url' => array('text', array(
            'label' => 'Stream Now-Playing Data URL',
            'class' => 'half-width',

            'description' => 'Optionally provide the address (including http[s]://) where the PVL service can connect to view now-playing data about your station, if it would not be the default for your stream.',
        )),

        'submit'        => array('submit', array(
            'type'  => 'submit',
            'label' => 'Save Changes',
            'helper' => 'formButton',
            'class' => 'ui-button btn-large',
        )),

    ),
);