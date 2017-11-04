<?php
return [
    'method' => 'post',
    'elements' => [

        'path' => [
            'text',
            [
                'label' => _('File Name'),
                'description' => _('The relative path of the file in the station\'s media directory.'),
            ],
        ],

        'title' => [
            'text',
            [
                'label' => _('Song Title'),
            ]
        ],

        'artist' => [
            'text',
            [
                'label' => _('Song Artist'),
            ]
        ],

        'album' => [
            'text',
            [
                'label' => _('Song Album'),
            ]
        ],

        'lyrics' => [
            'textarea',
            [
                'label' => _('Song Lyrics'),
            ]
        ],

        'art' => [
            'file',
            [
                'label' => _('Replace Album Cover Art'),
                'type' => 'image',
            ]
        ],

        'isrc' => [
            'text',
            [
                'label' => _('ISRC'),
                'description' => _('International Standard Recording Code, used for licensing reports.'),
            ]
        ],

        'length' => [
            'text',
            [
                'label' => _('Song Length (seconds)'),
                'disabled' => true,
            ]
        ],

        'fade_overlap' => [
            'text',
            [
                'label' => _('Custom Fading: Overlap Time (seconds)'),
                'description' => _('The time that this song should overlap its surrounding songs when fading. Leave blank to use the system default.'),

            ]
        ],

        'fade_in' => [
            'text',
            [
                'label' => _('Custom Fading: Fade-In Time (seconds)'),
                'description' => _('The time period that the song should fade in. Leave blank to use the system default.'),
            ]
        ],

        'fade_out' => [
            'text',
            [
                'label' => _('Custom Fading: Fade-Out Time (seconds)'),
                'description' => _('The time period that the song should fade out. Leave blank to use the system default.'),
            ]
        ],

        'cue_in' => [
            'text',
            [
                'label' => _('Custom Cues: Cue-In Point (seconds)'),
                'description' => _('Seconds from the start of the song that the AutoDJ should start playing.'),
            ]
        ],

        'cue_out' => [
            'text',
            [
                'label' => _('Custom Cues: Cue-Out Point (seconds)'),
                'description' => _('Seconds from the start of the song that the AutoDJ should stop playing.'),
            ]
        ],

        'submit' => [
            'submit',
            [
                'type' => 'submit',
                'label' => _('Save Changes'),
                'class' => 'ui-button btn-lg btn-primary',
            ]
        ],

    ],
];