<?php
return [
    'method' => 'post',

    'elements' => [

        'public_theme' => [
            'radio',
            [
                'label' => _('Base Theme for Public Pages'),
                'description' => _('Select a theme to use as a base for station public pages and the login page.'),
                'options' => $settings['themes']['available'],
                'default' => $settings['themes']['default'],
            ]
        ],

        'hide_album_art' => [
            'radio',
            [
                'label' => _('Hide Album Art on Public Pages'),
                'description' => _('If selected, album art will not display on public-facing radio pages.'),
                'default' => 0,
                'options' => [
                    0 => _('No'),
                    1 => _('Yes'),
                ],
            ]
        ],

        'default_album_art_url' => [
            'text',
            [
                'label' => _('Default Album Art URL'),
                'description' => _('If a song has no album art, this URL will be listed instead. Leave blank to use the standard placeholder art.'),
                'default' => '',
            ],
        ],

        'hide_product_name' => [
            'radio',
            [
                'label' => _('Hide AzuraCast Branding on Public Pages'),
                'description' => _('If selected, this will remove the AzuraCast branding from public-facing pages.'),
                'default' => 0,
                'options' => [
                    0 => _('No'),
                    1 => _('Yes'),
                ],
            ]
        ],

        'custom_css_public' => [
            'textarea',
            [
                'label' => _('Custom CSS for Public Pages'),
                'description' => _('This CSS will be applied to the station public pages and login page.'),
                'class' => 'css-editor',
                'filter' => function($val) { return strip_tags($val); }
            ]
        ],

        'custom_js_public' => [
            'textarea',
            [
                'label' => _('Custom JS for Public Pages'),
                'description' => _('This javascript code will be applied to the station public pages and login page.'),
                'class' => 'js-editor',
                'filter' => function($val) { return strip_tags($val); }
            ]
        ],

        'custom_css_internal' => [
            'textarea',
            [
                'label' => _('Custom CSS for Internal Pages'),
                'description' => _('This CSS will be applied to the main management pages, like this one.'),
                'class' => 'css-editor',
                'filter' => function($val) { return strip_tags($val); }
            ],
        ],



        'submit' => [
            'submit',
            [
                'type' => 'submit',
                'label' => _('Save Changes'),
                'class' => 'btn btn-lg btn-primary',
            ]
        ],

    ],
];