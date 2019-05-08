<?php
/** @var \App\Http\Router $router */

return [
    'method' => 'post',
    'groups' => [
        'core_metadata' => [
            'legend' => __('Song Metadata'),
            'legend_class' => 'd-none',
            'elements' => [
                'path' => [
                    'text',
                    [
                        'label' => __('File Name'),
                        'description' => __('The relative path of the file in the station\'s media directory.'),
                        'label_class' => 'mb-2',
                        'form_group_class' => 'col-md-6 mt-1',
                    ],
                ],

                'title' => [
                    'text',
                    [
                        'label' => __('Song Title'),
                        'label_class' => 'mb-2',
                        'form_group_class' => 'col-md-6 mt-1',
                    ]
                ],

                'artist' => [
                    'text',
                    [
                        'label' => __('Song Artist'),
                        'label_class' => 'mb-2',
                        'form_group_class' => 'col-md-6 mt-1',
                    ]
                ],

                'album' => [
                    'text',
                    [
                        'label' => __('Song Album'),
                        'label_class' => 'mb-2',
                        'form_group_class' => 'col-md-6 mt-1',
                    ]
                ],

                'lyrics' => [
                    'textarea',
                    [
                        'label' => __('Song Lyrics'),
                        'label_class' => 'mb-2',
                        'form_group_class' => 'col-md-6 mt-1',
                    ]
                ],

                'art' => [
                    'file',
                    [
                        'label' => __('Replace Album Cover Art'),
                        'type' => 'image',
                        'label_class' => 'mb-2',
                        'form_group_class' => 'col-md-6 mt-1',
                        'button_text' => __('Select File'),
                        'button_icon' => 'cloud_upload',
                    ]
                ],

                'isrc' => [
                    'text',
                    [
                        'label' => __('ISRC'),
                        'description' => __('International Standard Recording Code, used for licensing reports.'),
                        'label_class' => 'mb-2',
                        'form_group_class' => 'col-md-6 mt-1',
                    ]
                ],
            ],
        ],

        'custom_fields' => [
            'legend' => __('Custom Fields'),
            'legend_class' => 'col-sm-12',
            'description' => __('Administrators can customize the fields that appear here in the <a href="%s">administration page</a>.', $router->named('admin:custom_fields:index')),
            'description_class' => 'col-sm-12',
            'elements' => [
            ],
        ],

        'autodj_controls' => [
            'legend' => __('Control Song Playback'),
            'legend_class' => 'col-sm-12 mb-4',
            'class' => 'advanced',

            'elements' => [

                'length' => [
                    'text',
                    [
                        'label' => __('Song Length (seconds)'),
                        'disabled' => true,
                        'label_class' => 'mb-2',
                        'form_group_class' => 'col-md-6 mt-1',
                    ]
                ],

                'fade_overlap' => [
                    'text',
                    [
                        'label' => __('Custom Fading: Overlap Time (seconds)'),
                        'description' => __('The time that this song should overlap its surrounding songs when fading. Leave blank to use the system default.'),
                        'label_class' => 'mb-2',
                        'form_group_class' => 'col-md-6 mt-1',
                    ]
                ],

                'fade_in' => [
                    'text',
                    [
                        'label' => __('Custom Fading: Fade-In Time (seconds)'),
                        'description' => __('The time period that the song should fade in. Leave blank to use the system default.'),
                        'label_class' => 'mb-2',
                        'form_group_class' => 'col-md-6 mt-1',
                    ]
                ],

                'fade_out' => [
                    'text',
                    [
                        'label' => __('Custom Fading: Fade-Out Time (seconds)'),
                        'description' => __('The time period that the song should fade out. Leave blank to use the system default.'),
                        'label_class' => 'mb-2',
                        'form_group_class' => 'col-md-6 mt-1',
                    ]
                ],

                'cue_in' => [
                    'text',
                    [
                        'label' => __('Custom Cues: Cue-In Point (seconds)'),
                        'description' => __('Seconds from the start of the song that the AutoDJ should start playing.'),
                        'label_class' => 'mb-2',
                        'form_group_class' => 'col-md-6 mt-1',
                    ]
                ],

                'cue_out' => [
                    'text',
                    [
                        'label' => __('Custom Cues: Cue-Out Point (seconds)'),
                        'description' => __('Seconds from the start of the song that the AutoDJ should stop playing.'),
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
                        'class' => 'ui-button btn-lg btn-primary',
                        'form_group_class' => 'col-sm-12 mt-3',
                    ]
                ],

            ],
        ],

    ],
];
