<?php
return array(
    'method'        => 'post',
    'enctype'       => 'multipart/form-data',

    'elements' => array(

        'name' => array('text', array(
            'label' => 'Playlist Name',
            'required' => true,
        )),

        'weight' => array('radio', array(
            'label' => 'Playlist Weight',
            'description' => 'How often the playlist\'s songs will be played. 1 is the most infrequent, 10 is the most frequent.',
            'default' => 5,
            'required' => true,
            'class' => 'inline',
            'options' => array(
                1 => '1 - Lowest',
                2 => '2',
                3 => '3',
                4 => '4',
                5 => '5 - Default',
                6 => '6',
                7 => '7',
                8 => '8',
                9 => '9',
                10 => '10 - Highest',
            ),
        )),

        'submit'        => array('submit', array(
            'type'  => 'submit',
            'label' => 'Save Changes',
            'helper' => 'formButton',
            'class' => 'ui-button btn-lg btn-primary',
        )),

    ),
);