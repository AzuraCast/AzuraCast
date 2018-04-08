<?php
return [
    'method' => 'post',

    'elements' => [

        'public_theme' => [
            'radio',
            [
                'label' => __('Base Theme for Public Pages'),
                'description' => __('Select a theme to use as a base for station public pages and the login page.'),
                'options' => [
                    'light' => __('Light').' ('.__('Default').')',
                    'dark' => __('Dark'),
                ],
                'default' => $settings['themes']['default'],
            ]
        ],

        'hide_album_art' => [
            'radio',
            [
                'label' => __('Hide Album Art on Public Pages'),
                'description' => __('If selected, album art will not display on public-facing radio pages.'),
                'default' => 0,
                'options' => [
                    0 => __('No'),
                    1 => __('Yes'),
                ],
            ]
        ],

        'homepage_redirect_url' => [
            'text',
            [
                'label' => __('Homepage Redirect URL'),
                'description' => __('If a visitor is not signed in and visits the AzuraCast homepage, you can automatically redirect them to the URL specified here. Leave blank to redirect them to the login screen by default.'),
                'default' => '',
            ]
        ],

        'default_album_art_url' => [
            'text',
            [
                'label' => __('Default Album Art URL'),
                'description' => __('If a song has no album art, this URL will be listed instead. Leave blank to use the standard placeholder art.'),
                'default' => '',
            ],
        ],

        'hide_product_name' => [
            'radio',
            [
                'label' => __('Hide AzuraCast Branding on Public Pages'),
                'description' => __('If selected, this will remove the AzuraCast branding from public-facing pages.'),
                'default' => 0,
                'options' => [
                    0 => __('No'),
                    1 => __('Yes'),
                ],
            ]
        ],

        'custom_css_public' => [
            'textarea',
            [
                'label' => __('Custom CSS for Public Pages'),
                'description' => __('This CSS will be applied to the station public pages and login page.'),
                'class' => 'css-editor',
                'filter' => function($val) { return strip_tags($val); }
            ]
        ],

        'custom_js_public' => [
            'textarea',
            [
                'label' => __('Custom JS for Public Pages'),
                'description' => __('This javascript code will be applied to the station public pages and login page.'),
                'class' => 'js-editor',
                'filter' => function($val) { return strip_tags($val); }
            ]
        ],

        'custom_css_internal' => [
            'textarea',
            [
                'label' => __('Custom CSS for Internal Pages'),
                'description' => __('This CSS will be applied to the main management pages, like this one.'),
                'class' => 'css-editor',
                'filter' => function($val) { return strip_tags($val); }
            ],
        ],



        'submit' => [
            'submit',
            [
                'type' => 'submit',
                'label' => __('Save Changes'),
                'class' => 'btn btn-lg btn-primary',
            ]
        ],

    ],
];