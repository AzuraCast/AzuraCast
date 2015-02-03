<?php
return array(
    'method'        => 'post',
    'enctype'       => 'multipart/form-data',

    'groups' => array(
        'profile' => array(
            'legend' => 'Metadata',
            'elements' => array(

                'text' => array('text', array(
                    'label' => 'Full Text',
                    'description' => 'Typically in the form of "Artist - Title". Should not be edited.',
                    'class' => 'half-width',
                    'disabled' => 'disabled',
                )),

                'artist' => array('text', array(
                    'label' => 'Artist Name',
                    'class' => 'half-width',
                    'description' => 'For multiple artists, format should be "Artist 1, Artist 2"',
                )),

                'title' => array('text', array(
                    'label' => 'Song Title',
                    'class' => 'half-width',
                )),

            ),
        ),

        'submit_grp' => array(
            'elements'       => array(
                'submit'        => array('submit', array(
                    'type'  => 'submit',
                    'label' => 'Save Changes',
                    'helper' => 'formButton',
                    'class' => 'ui-button',
                )),
            ),
        ),

    ),
);