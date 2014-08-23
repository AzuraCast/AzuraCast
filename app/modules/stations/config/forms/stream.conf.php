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

        'type' => array('radio', array(
            'label' => 'Broadcast Source',
            'multiOptions' => array(
                'shoutcast1'        => 'ShoutCast v1',
                'shoutcast2'        => 'ShoutCast v2',
                'icecast'           => 'IceCast',
                'icebreath'         => 'IceCast with IceBreath Tool',
                'stream'            => 'Other / Video Stream',
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
                        <b>Broadcast URL:</b> <span class="text-disabled">http://listen.fillyradio.com:8000</span>/;stream.nsv<br>
                        <b>Now Playing URL:</b> <span class="text-disabled">http://listen.fillyradio.com:8000</span>/7.html
                    </dd>

                    <dt>SC 2 &amp; CentovaCast</dt>
                    <dd>
                        <b>Broadcast URL:</b> <span class="text-disabled">http://pvlradio.bravelyblue.com:8020</span>/stream?sid=1;stream.nsv<br>
                        <b>Now Playing URL:</b> <span class="text-disabled">http://pvlradio.bravelyblue.com:8020</span>/stats?sid=1
                    </dd>

                    <dt>IceCast</dt>
                    <dd>
                        <b>Broadcast URL:</b> <span class="text-disabled">http://www.radiobrony.fr:8000</span>/live<br>
                        <b>Now Playing URL:</b> <span class="text-disabled">http://www.radiobrony.fr:8000</span>/
                    </dd>

                    <dt>IceBreath</dt>
                    <dd>
                        <b>Broadcast URL:</b> <span class="text-disabled">http://url.com:8000</span>/live.mp3<br>
                        <b>Now Playing URL:</b> <span class="text-disabled">http://url.com</span>/icebreath/icecast/stats/live.mp3
                    </dd>

                    <dt>LiveStream</dt>
                    <dd>
                        <b>Broadcast URL:</b> <span class="text-disabled">http://www.livestream.com</span>/ponyvillelive<br>
                        <b>Now Playing URL:</b> <span class="text-disabled">http://x</span>ponyvillelive<span class="text-disabled">x.api.channel.livestream.com/2.0/livestatus.xml</span>
                    </dd>
                </dl>

                <p>If you have any questions, please contact the Ponyville Live! administrators prior to updating your stream.</p>',
        )),

        'stream_url' => array('text', array(
            'label' => 'Stream Broadcast URL',
            'class' => 'half-width',

            'description' => 'The address where listeners can tune in to your radio station or video stream. Include the full web address, i.e. http://streamurl.stream.com:8000/."',
            'required' => true,
        )),

        'nowplaying_url' => array('text', array(
            'label' => 'Stream Now-Playing Data URL',
            'class' => 'half-width',

            'description' => 'The address where listeners can tune in to your radio station or video stream. Include the full web address, i.e. http://streamurl.stream.com:8000/."',
            'required' => true,
        )),

        'hidden_from_player' => array('radio', array(
            'label' => 'Hide Stream from PVL Web Player',
            'multiOptions' => array(0 => 'No', 1 => 'Yes'),
            'default' => 0,

            'description' => 'Some types of streams cannot be played by the PVL web player, including most AAC+ streams. If this stream should be included in statistics but not listed on the player, select "Yes" here.',
            'required' => true,
        )),

        'submit'        => array('submit', array(
            'type'  => 'submit',
            'label' => 'Save Changes',
            'helper' => 'formButton',
            'class' => 'ui-button btn-large',
        )),

    ),
);