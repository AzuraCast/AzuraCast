<?php
/**
 * Settings form.
 */

return [
    /**
     * Form Configuration
     */
    'form' => [
        'method' => 'post',

        'groups' => [

            'system' => [
                'elements' => [

                    'instance_name' => [
                        'text',
                        [
                            'label' => _('AzuraCast Instance Name'),
                            'description' => _('This name will appear as a sub-header next to the AzuraCast logo, to help identify this server.'),
                        ],
                    ],

                    'base_url' => [
                        'text',
                        [
                            'label' => _('Site Base URL'),
                            'description' => _('The base URL where this service is located. Use either the external IP address or fully-qualified domain name (if one exists) pointing to this server.'),
                            'default' => $_SERVER['HTTP_HOST'],
                            'filter' => function($str) {
                                return str_replace(['http://', 'https://'], ['', ''], trim($str));
                            },
                        ]
                    ],

                    'prefer_browser_url' => [
                        'radio',
                        [
                            'label' => _('Prefer Browser URL (If Available)'),
                            'description' => _('If this setting is set to "Yes", the browser URL will be used instead of the base URL when it\'s available. Set to "No" to always use the base URL.'),
                            'options' => [
                                0 => 'No',
                                1 => 'Yes',
                            ],
                            'default' => 0,
                        ]
                    ],

                    'always_use_ssl' => [
                        'radio',
                        [
                            'label' => _('Always Use HTTPS'),
                            'description' => _('Set to "Yes" to always use "https://" secure URLs.'),
                            'options' => [
                                0 => 'No',
                                1 => 'Yes',
                            ],
                            'default' => 0,
                        ]
                    ],

                    'use_radio_proxy' => [
                        'radio',
                        [
                            'label' => _('Use Web Proxy for Radio'),
                            'description' => _('By default, radio stations broadcast on their own ports (i.e. 8000). If you\'re using a service like CloudFlare or accessing your radio station by SSL, you should enable this feature, which routes all radio through the web ports (80 and 443).'),
                            'options' => [
                                0 => 'No',
                                1 => 'Yes',
                            ],
                            'default' => 0,
                        ]
                    ],

                ],
            ],

            'api_keys' => [
                'legend' => _('Advanced: Third-Party API Access'),
                'description' => _('For some features, AzuraCast must connect to third-party API services. These services are optional.'),

                'elements' => [

                    'gmaps_api_key' => [
                        'text',
                        [
                            'label' => _('Google Maps API Key'),
                            'description' => sprintf(_('To see a map of your listeners, provide a Google Maps API key. You can obtain one from the <a href="%s" target="_blank">Google Developer Console</a>. Make sure to enable the "Google Maps JavaScript API" as well.'), 'https://console.developers.google.com'),
                        ]
                    ],

                ],
            ],

            'submit' => [
                'legend' => '',
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
    ],
];