<?php
/** @var \App\Http\Router $router */

return [
    'method' => 'post',
    'groups' => [
        'core_metadata' => [
            'use_grid' => true,
            'elements' => [
                'path' => [
                    'text',
                    [
                        'label' => __('File Name'),
                        'description' => __('The relative path of the file in the station\'s media directory.'),
                        'form_group_class' => 'col-md-6',
                    ],
                ],

                'title' => [
                    'text',
                    [
                        'label' => __('Song Title'),
                        'form_group_class' => 'col-md-6',
                    ]
                ],

                'artist' => [
                    'text',
                    [
                        'label' => __('Song Artist'),
                        'form_group_class' => 'col-md-6',
                    ]
                ],

                'album' => [
                    'text',
                    [
                        'label' => __('Song Album'),
                        'form_group_class' => 'col-md-6',
                    ]
                ],

                'lyrics' => [
                    'textarea',
                    [
                        'label' => __('Song Lyrics'),
                        'form_group_class' => 'col-md-6',
                    ]
                ],

                'art' => [
                    'file',
                    [
                        'label' => __('Replace Album Cover Art'),
                        'type' => 'image',
                        'form_group_class' => 'col-md-6',
                        'button_text' => __('Select File'),
                        'button_icon' => 'cloud_upload',
                    ]
                ],

                'isrc' => [
                    'text',
                    [
                        'label' => __('ISRC'),
                        'description' => __('International Standard Recording Code, used for licensing reports.'),
                        'form_group_class' => 'col-md-6',
                    ]
                ],
            ],
        ],

        'custom_fields' => [
            'legend' => __('Custom Fields'),
            'description' => __('Administrators can customize the fields that appear here in the <a href="%s">administration page</a>.', $router->named('admin:custom_fields:index')),
            'elements' => [
            ],
        ],

        'autodj_controls' => [
            'use_grid' => true,
            'legend' => __('Control Song Playback'),
            'class' => 'advanced',

            'elements' => [

                'length' => [
                    'text',
                    [
                        'label' => __('Song Length (seconds)'),
                        'disabled' => true,
                        'form_group_class' => 'col-md-6',
                    ]
                ],

                'fade_overlap' => [
                    'text',
                    [
                        'label' => __('Custom Fading: Overlap Time (seconds)'),
                        'description' => __('The time that this song should overlap its surrounding songs when fading. Leave blank to use the system default.'),
                        'form_group_class' => 'col-md-6',
                    ]
                ],

                'fade_in' => [
                    'text',
                    [
                        'label' => __('Custom Fading: Fade-In Time (seconds)'),
                        'description' => __('The time period that the song should fade in. Leave blank to use the system default.'),
                        'form_group_class' => 'col-md-6',
                    ]
                ],

                'fade_out' => [
                    'text',
                    [
                        'label' => __('Custom Fading: Fade-Out Time (seconds)'),
                        'description' => __('The time period that the song should fade out. Leave blank to use the system default.'),
                        'form_group_class' => 'col-md-6',
                    ]
                ],

                'cue_in' => [
                    'text',
                    [
                        'label' => __('Custom Cues: Cue-In Point (seconds)'),
                        'description' => __('Seconds from the start of the song that the AutoDJ should start playing.'),
                        'form_group_class' => 'col-md-6',
                    ]
                ],

                'cue_out' => [
                    'text',
                    [
                        'label' => __('Custom Cues: Cue-Out Point (seconds)'),
                        'description' => __('Seconds from the start of the song that the AutoDJ should stop playing.'),
                        'form_group_class' => 'col-md-6',
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
                        'class' => 'ui-button btn-lg btn-primary',
                    ]
                ],

            ],
        ],

    ],
];
