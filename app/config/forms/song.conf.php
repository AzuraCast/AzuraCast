<?php
return [
    'method' => 'post',
    'enctype' => 'multipart/form-data',

    'groups' => [
        'profile' => [
            'legend' => _('Metadata'),
            'elements' => [

                'text' => [
                    'text',
                    [
                        'label' => _('Full Text'),
                        'description' => 'Typically in the form of "Artist - Title". Should not be edited.',
                        'class' => 'half-width',
                        'disabled' => 'disabled',
                    ]
                ],

                'artist' => [
                    'text',
                    [
                        'label' => _('Artist Name'),
                        'class' => 'half-width',
                        'description' => 'For multiple artists, format should be "Artist 1, Artist 2"',
                    ]
                ],

                'title' => [
                    'text',
                    [
                        'label' => _('Song Title'),
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
                        'label' => _('Save Changes'),
                        'class' => 'btn btn-lg btn-primary',
                    ]
                ],
            ],
        ],

    ],
];