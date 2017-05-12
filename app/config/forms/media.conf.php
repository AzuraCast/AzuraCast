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

        'submit' => [
            'submit',
            [
                'type' => 'submit',
                'label' => _('Save Changes'),
                'helper' => 'formButton',
                'class' => 'ui-button btn-lg btn-primary',
            ]
        ],

    ],
];