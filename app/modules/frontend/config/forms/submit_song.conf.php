<?php

return array(   
    'method'        => 'post',
    'enctype'       => 'multipart/form-data',

    'groups' => array(

        'about' => array(
            'legend' => 'About Song Submissions',
            'elements' => array(

                'about_text' => array('markup', array(
                    'markup' => '

                    ',
                )),
            ),
        ),

        'metadata' => array(
            'legend' => 'Song Metadata',
            'elements' => array(

                'song_url' => array('file', array(
                    'label' => 'Song to Upload',
                    'description' => 'Songs should be MP3s, with a bitrate of at least 128kbps. Maximum file size is 20MB.',
                )),

                'title' => array('text', array(
                    'label' => 'Song Title',
                    'class' => 'half-width',
                    'required' => true,
                )),

                'artist' => array('text', array(
                    'label' => 'Song Artist Name',
                    'class' => 'half-width',
                    'required' => true,
                )),

            ),
        ),

        'stations' => array(
            'legend' => 'Station Selection',
            'elements' => array(

                'stations' => array('multiCheckbox', array(
                    'label' => 'Select Stations',
                    'multiOptions' => $stations,
                    'required' => true,
                )),

            ),
        ),

        'submit_grp' => array(
            'elements' => array(
                'submit'        => array('submit', array(
                    'type'  => 'submit',
                    'label' => 'Submit Song',
                    'helper' => 'formButton',
                    'class' => 'ui-button btn-large',
                )),
            ),
        ),

    ),
);