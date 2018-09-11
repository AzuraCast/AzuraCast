<?php
/** @var \App\Http\Router $router */

return [
    'method' => 'post',
    'groups' => [
        'core_metadata' => [
            'legend' => __('Song Metadata'),
            'elements' => [
                'path' => [
                    'text',
                    [
                        'label' => __('File Name'),
                        'description' => __('The relative path of the file in the station\'s media directory.'),
                    ],
                ],

                'title' => [
                    'text',
                    [
                        'label' => __('Song Title'),
                    ]
                ],

                'artist' => [
                    'text',
                    [
                        'label' => __('Song Artist'),
                    ]
                ],

                'album' => [
                    'text',
                    [
                        'label' => __('Song Album'),
                    ]
                ],

                'lyrics' => [
                    'textarea',
                    [
                        'label' => __('Song Lyrics'),
                    ]
                ],

                'art' => [
                    'file',
                    [
                        'label' => __('Replace Album Cover Art'),
                        'type' => 'image',
                    ]
                ],

                'isrc' => [
                    'text',
                    [
                        'label' => __('ISRC'),
                        'description' => __('International Standard Recording Code, used for licensing reports.'),
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
            'legend' => __('Control Song Playback'),
            'class' => 'advanced',

            'elements' => [

                'length' => [
                    'text',
                    [
                        'label' => __('Song Length (seconds)'),
                        'disabled' => true,
                    ]
                ],

                'fade_overlap' => [
                    'text',
                    [
                        'label' => __('Custom Fading: Overlap Time (seconds)'),
                        'description' => __('The time that this song should overlap its surrounding songs when fading. Leave blank to use the system default.'),

                    ]
                ],

                'fade_in' => [
                    'text',
                    [
                        'label' => __('Custom Fading: Fade-In Time (seconds)'),
                        'description' => __('The time period that the song should fade in. Leave blank to use the system default.'),
                    ]
                ],

                'fade_out' => [
                    'text',
                    [
                        'label' => __('Custom Fading: Fade-Out Time (seconds)'),
                        'description' => __('The time period that the song should fade out. Leave blank to use the system default.'),
                    ]
                ],

                'cue_in' => [
                    'text',
                    [
                        'label' => __('Custom Cues: Cue-In Point (seconds)'),
                        'description' => __('Seconds from the start of the song that the AutoDJ should start playing.'),
                    ]
                ],

                'cue_out' => [
                    'text',
                    [
                        'label' => __('Custom Cues: Cue-Out Point (seconds)'),
                        'description' => __('Seconds from the start of the song that the AutoDJ should stop playing.'),
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
