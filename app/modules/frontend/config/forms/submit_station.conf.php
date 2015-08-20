<?php 
return array(   
    'method'        => 'post',
    'enctype'       => 'multipart/form-data',

    'groups' => array(

        'about' => array(
            'legend' => 'About Ponyville Live! Partner Stations',
            'elements' => array(

                'about_text' => array('markup', array(
                    'markup' => '
                        <p>We are honored that your station is interested in joining the Ponyville Live family. For over two years, the Ponyville Live (or, as we call it, PVL) network of stations has delivered countless hours of live programming, 24/7 radio stations, video streams, convention coverage, and more to Bronies across the world.</p>

                        <p><b>The PVL Promise:</b> Joining the PVL family of radio stations brings your station to a whole new audience, without sacrificing your station\'s independence or creativity. We do not influence the scheduling, staffing, or content of your station (as long as it\'s legal). Our goal is to give you and your team the opportunities and exposure that large stations would receive, but without needing to build an audience from scratch.</p>

                        <p>In order for us to add your station to our web site, plugins, and apps, we need some information about your station. This information also helps our existing station administrators determine if your station is a good fit for the network.</p>

                        <p><b>All new stations joining PVL must be approved by the existing station leaders.</b> This process may take a week or two after you have submitted your station. If you don\'t get voted in the first time, though, don\'t worry; our team will let you know any feedback they have, and you can always request that the team re-vote on your station after you have made any changes.</p>

                        <p>If you have any questions about the new station submission process, just contact our leadership at <a href="mailto:pr@ponyvillelive.com">pr@ponyvillelive.com</a>. Thank you again for your submission, and we look forward to working with you and your team!</p>
                    ',
                )),
            ),
        ),

        'basic_info' => array(
            'legend' => 'Basic Information',
            'description' => 'Tell us some essential basic information about your station.',

            'elements' => array(

                'name' => array('text', array(
                    'label' => 'Station Name',
                    'class' => 'half-width',
                    'required' => true,

                    'description' => 'The full name of your station that you use to advertise it.',
                )),

                'category' => array('radio', array(
                    'label' => 'Type of Station',
                    'required' => true,
                    'multiOptions' => array(
                        'audio' => 'Radio Station',
                        'video' => 'Video Stream',
                    ),
                )),

                'genre' => array('text', array(
                    'label' => 'Station Genre',
                    'required' => true,

                    'description' => 'A few words that describe what kind of music or videos are played on your station. For example, if you mostly play rock music, just enter "Rock Music"; if your audience is mainly an international group, enter the group\'s name (i.e. "German Brony Radio").',
                )),

                'description' => array('textarea', array(
                    'label' => 'Station Description',
                    'class' => 'full-width half-height',
                    'required' => true,

                    'description' => 'A full description of your station, its background, and its goals. Try to be as thorough as possible!'
                )),

                'web_url' => array('text', array(
                    'label' => 'Station Homepage (Web Address)',
                    'class' => 'half-width',
                    'required' => true,

                    'description' => 'The homepage where users can find out more information about your station. Be sure to include the full address, starting with "http".',
                )),

                'twitter_url' => array('text', array(
                    'label' => 'Twitter Username (Optional)',
                    'description' => 'If you have a username on Twitter, enter it here. You can either enter "@Username" or the actual URL of your Twitter profile.',
                )),

            ),
        ),

        'technical_info' => array(
            'legend' => 'Technical Information',
            'description' => 'Please provide as much of this information as you can. If you don\'t know the answer to these questions, you can leave them blank, or contact us using the e-mail listed above.',

            'elements' => array(

                'image_url' => array('image', array(
                    'label' => 'Station Logo Graphic',

                    'description' => 'If you have a logo for your station, upload it here. Images should be square in size, at least 150x150px large, and preferably PNG.',
                )),

                'type' => array('radio', array(
                    'label' => 'Station Broadcast Source',
                    'belongsTo' => 'stream',

                    'multiOptions' => array(
                        'video'             => 'Video Stream',
                        'shoutcast1'        => 'ShoutCast v1',
                        'shoutcast2'        => 'ShoutCast v2',
                        'icecast'           => 'IceCast',
                        'stream'            => 'Other',
                    ),

                    'description' => 'The system used by your station to stream audio or video. If you are a video stream, just select "Other".',
                )),

                'stream_url' => array('text', array(
                    'label' => 'Stream URL',
                    'belongsTo' => 'stream',
                    'class' => 'half-width',

                    'description' => 'The address where listeners can tune in to your radio station or video stream. Include the full web address, i.e. http://streamurl.stream.com:8000/.',
                )),

            ),
        ),

        'submit_grp' => array(
            'elements' => array(
                'submit'        => array('submit', array(
                    'type'  => 'submit',
                    'label' => 'Submit Station',
                    'helper' => 'formButton',
                    'class' => 'ui-button btn-large',
                )),
            ),
        ),

    ),
);