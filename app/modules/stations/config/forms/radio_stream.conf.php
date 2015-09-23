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

        'type' => array('radio', array(
            'label' => 'Broadcast Source',
            'multiOptions' => array(
                'shoutcast1'        => 'ShoutCast v1',
                'shoutcast2'        => 'ShoutCast v2',
                'icecast'           => 'IceCast',
                'icebreath'         => 'IceBreath',
            ),

            'description' => 'The system used by your station to stream audio or video. If you are a video stream, just select "Other".',
        )),

        'url_examples' => array('markup', array(
            'label' => 'Common Broadcast and Now-Playing URLs',
            'markup' => '
                <p>If you are unsure of what to enter into the fields below, some examples of common stream/nowplaying URLs for various stream types are included below for reference. You may need to customize these to meet your station\'s requirements.</p>

                <dl class="dl-horizontal">
                    <dt>ShoutCast 1</dt>
                    <dd>
                        <b>Broadcast URL:</b> <span class="text-disabled">http://domainname.com:8000</span>/;stream.nsv<br>
                        <b>Now Playing URL:</b> <span class="text-disabled">http://domainname.com:8000</span>/7.html
                    </dd>

                    <dt>SC 2 &amp; CentovaCast</dt>
                    <dd>
                        <b>Broadcast URL:</b> <span class="text-disabled">http://domainname.com:8000</span>/stream?sid=1;stream.nsv<br>
                        <b>Now Playing URL:</b> <span class="text-disabled">http://domainname.com:8000</span>/stats?sid=1
                    </dd>

                    <dt>IceCast < 2.4</dt>
                    <dd>
                        <b>Broadcast URL:</b> <span class="text-disabled">http://domainname.com:8000</span>/live<br>
                        <b>Now Playing URL:</b> <span class="text-disabled">http://domainname.com:8000</span>/
                    </dd>

                    <dt>Icecast >= 2.4</dt>
                    <dd>
                        <b>Broadcast URL:</b> <span class="text-disabled">http://domainname.com:8000</span>/live<br>
                        <b>Now Playing URL:</b> <span class="text-disabled">http://domainname.com:8000</span>/status-json.xsl?mount=/live
                    </dd>

                    <dt>IceBreath</dt>
                    <dd>
                        <b>Broadcast URL:</b> <span class="text-disabled">http://domainname.com:8000</span>/live.mp3<br>
                        <b>Now Playing URL:</b> <span class="text-disabled">http://domainname.com</span>/icebreath/icecast/stats/live.mp3
                    </dd>
                </dl>

                <p>If you have any questions, please contact the Ponyville Live! administrators prior to updating your stream.</p>',
        )),

        'stream_url' => array('text', array(
            'label' => 'Stream Broadcast URL',
            'class' => 'half-width',

            'description' => 'The address (including http[s]://) where listeners can tune in to your radio station or video stream.',
            'required' => true,
        )),

        'nowplaying_url' => array('text', array(
            'label' => 'Stream Now-Playing Data URL',
            'class' => 'half-width',

            'description' => 'The address (including http[s]://) where the PVL service can connect to view now-playing data about your station.',
            'required' => true,
        )),

        'hidden_from_player' => array('radio', array(
            'label' => 'Hide Stream from PVL Web Player',
            'multiOptions' => array(0 => 'No', 1 => 'Yes'),
            'default' => 0,

            'description' => 'Some types of streams cannot be played by the PVL web player, including most AAC+ streams. If this stream should be included in statistics but not listed on the player, select "Yes" here.',
        )),

        'submit'        => array('submit', array(
            'type'  => 'submit',
            'label' => 'Save Changes',
            'helper' => 'formButton',
            'class' => 'ui-button btn-large',
        )),

    ),
);