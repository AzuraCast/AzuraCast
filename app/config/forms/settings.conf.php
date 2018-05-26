<?php
return [
    'groups' => [

        'system' => [
            'elements' => [

                'instance_name' => [
                    'text',
                    [
                        'label' => __('AzuraCast Instance Name'),
                        'description' => __('This name will appear as a sub-header next to the AzuraCast logo, to help identify this server.'),
                    ],
                ],

                'base_url' => [
                    'text',
                    [
                        'label' => __('Site Base URL'),
                        'description' => __('The base URL where this service is located. Use either the external IP address or fully-qualified domain name (if one exists) pointing to this server.'),
                        'default' => $_SERVER['HTTP_HOST'],
                        'filter' => function($str) {
                            return str_replace(['http://', 'https://'], ['', ''], trim($str));
                        },
                    ]
                ],

                'prefer_browser_url' => [
                    'radio',
                    [
                        'label' => __('Prefer Browser URL (If Available)'),
                        'description' => __('If this setting is set to "Yes", the browser URL will be used instead of the base URL when it\'s available. Set to "No" to always use the base URL.'),
                        'choices' => [
                            0 => __('No'),
                            1 => __('Yes'),
                        ],
                        'default' => 0,
                    ]
                ],

                'always_use_ssl' => [
                    'radio',
                    [
                        'label' => __('Always Use HTTPS'),
                        'description' => __('Set to "Yes" to always use "https://" secure URLs.'),
                        'choices' => [
                            0 => __('No'),
                            1 => __('Yes'),
                        ],
                        'default' => 0,
                    ]
                ],

                'use_radio_proxy' => [
                    'radio',
                    [
                        'label' => __('Use Web Proxy for Radio'),
                        'description' => __('By default, radio stations broadcast on their own ports (i.e. 8000). If you\'re using a service like CloudFlare or accessing your radio station by SSL, you should enable this feature, which routes all radio through the web ports (80 and 443).'),
                        'choices' => [
                            0 => __('No'),
                            1 => __('Yes'),
                        ],
                        'default' => 0,
                    ]
                ],

            ],
        ],

        'privacy' => [
            'legend' => __('Privacy Controls'),
            'description' => __('AzuraCast does not send your data to any external server. You can control how much data AzuraCast logs about your listeners here.'),

            'elements' => [

                'analytics' => [
                    'radio',
                    [
                        'label' => __('Analytics Collection'),
                        'description' => __('Aggregate listener statistics are used to show station reports across the system. IP-based listener statistics are used to view live listener tracking and may be required for royalty reports.'),

                        'choices' => [
                            \Entity\Analytics::LEVEL_ALL => __('<b>Full:</b> Collect aggregate listener statistics and IP-based listener statistics'),
                            \Entity\Analytics::LEVEL_NO_IP => __('<b>Limited:</b> Only collect aggregate listener statistics'),
                            \Entity\Analytics::LEVEL_NONE => __('<b>None:</b> Do not collect any listener analytics'),
                        ],
                        'default' => \Entity\Analytics::LEVEL_ALL,
                    ]
                ]

            ],
        ],

        'api_keys' => [
            'legend' => __('Advanced: Third-Party API Access'),
            'description' => __('For some features, AzuraCast must connect to third-party API services. These services are optional.'),

            'elements' => [

                'gmaps_api_key' => [
                    'text',
                    [
                        'label' => __('Google Maps API Key'),
                        'description' => sprintf(__('To see a map of your listeners, provide a Google Maps API key. You can obtain one from the <a href="%s" target="_blank">Google Developer Console</a>. Make sure to enable the "Google Maps JavaScript API" as well.'), 'https://console.developers.google.com'),
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
                        'label' => __('Save Changes'),
                        'class' => 'btn btn-lg btn-primary',
                    ]
                ],
            ],
        ],
    ],
];