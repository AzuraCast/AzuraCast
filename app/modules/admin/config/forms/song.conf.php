<?php
return [
    'method' => 'post',
    'enctype' => 'multipart/form-data',

    'groups' => [
        'profile' => [
            'legend' => 'Metadata',
            'elements' => [

                'text' => ['text', [
                    'label' => 'Full Text',
                    'description' => 'Typically in the form of "Artist - Title". Should not be edited.',
                    'class' => 'half-width',
                    'disabled' => 'disabled',
                ]],

                'artist' => ['text', [
                    'label' => 'Artist Name',
                    'class' => 'half-width',
                    'description' => 'For multiple artists, format should be "Artist 1, Artist 2"',
                ]],

                'title' => ['text', [
                    'label' => 'Song Title',
                    'class' => 'half-width',
                ]],

            ],
        ],

        'submit_grp' => [
            'elements' => [
                'submit' => ['submit', [
                    'type' => 'submit',
                    'label' => 'Save Changes',
                    'helper' => 'formButton',
                    'class' => 'btn btn-lg btn-primary',
                ]],
            ],
        ],

    ],
];