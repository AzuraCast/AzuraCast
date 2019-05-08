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
                        'label_class' => 'mb-2',
                        'form_group_class' => 'col-sm-12 mt-1',
                    ]
                ],

                'artist' => [
                    'text',
                    [
                        'label' => __('Artist Name'),
                        'class' => 'half-width',
                        'description' => 'For multiple artists, format should be "Artist 1, Artist 2"',
                        'label_class' => 'mb-2',
                        'form_group_class' => 'col-md-6 mt-1',
                    ]
                ],

                'title' => [
                    'text',
                    [
                        'label' => __('Song Title'),
                        'class' => 'half-width',
                        'label_class' => 'mb-2',
                        'form_group_class' => 'col-md-6 mt-1',
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
                        'form_group_class' => 'col-sm-12 mt-3',
                    ]
                ],
            ],
        ],

    ],
];
