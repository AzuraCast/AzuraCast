<?php
return [
    'method' => 'post',
    'enctype' => 'multipart/form-data',

    'groups' => [
        'profile' => [
            'legend' => __('Metadata'),
            'elements' => [

                'text' => [
                    'text',
                    [
                        'label' => __('Full Text'),
                        'description' => 'Typically in the form of "Artist - Title". Should not be edited.',
                        'class' => 'half-width',
                        'disabled' => 'disabled',
                    ]
                ],

                'artist' => [
                    'text',
                    [
                        'label' => __('Artist Name'),
                        'class' => 'half-width',
                        'description' => 'For multiple artists, format should be "Artist 1, Artist 2"',
                    ]
                ],

                'title' => [
                    'text',
                    [
                        'label' => __('Song Title'),
                        'class' => 'half-width',
                    ]
                ],

            ],
        ],

        'submit_grp' => [
            'elements' => [
                'submit' => [
                    'submit',
                    [
                        'type' => 'submit',
                        'label' => __('Save Changes'),
                        'class' => 'btn btn-lg btn-primary',
                    ]
                ],
            ],
        ],

    ],
];