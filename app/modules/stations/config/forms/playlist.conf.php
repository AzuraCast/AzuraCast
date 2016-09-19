<?php
return [
    'method' => 'post',
    'enctype' => 'multipart/form-data',

    'elements' => [

        'name' => ['text', [
            'label' => 'Playlist Name',
            'required' => true,
        ]],

        'weight' => ['radio', [
            'label' => 'Playlist Weight',
            'description' => 'How often the playlist\'s songs will be played. 1 is the most infrequent, 5 is the most frequent.',
            'default' => 3,
            'required' => true,
            'class' => 'inline',
            'options' => [
                1 => '1 - Lowest',
                2 => '2',
                3 => '3 - Default',
                4 => '4',
                5 => '5 - Highest',
            ],
        ]],

        'include_in_automation' => ['radio', [
            'label' => 'Include in Automated Assignment',
            'description' => 'If auto-assignment is enabled, use this playlist as one of the targets for songs to be redistributed into. This will overwrite the existing contents of this playlist.',
            'required' => true,
            'default' => '0',
            'options' => [
                0 => 'No',
                1 => 'Yes',
            ],
        ]],

        'submit' => ['submit', [
            'type' => 'submit',
            'label' => 'Save Changes',
            'helper' => 'formButton',
            'class' => 'ui-button btn-lg btn-primary',
        ]],

    ],
];