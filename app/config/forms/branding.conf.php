<?php

$settings = $di['app_settings'];

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

        'custom_css_public' => [
            'textarea',
            [
                'label' => _('Custom CSS for Public Pages'),
                'description' => _('This CSS will be applied to the station public pages and login page.'),
                'class' => 'css-editor',
            ]
        ],

        'custom_css_internal' => [
            'textarea',
            [
                'label' => _('Custom CSS for Internal Pages'),
                'description' => _('This CSS will be applied to the main management pages, like this one.'),
                'class' => 'css-editor',
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