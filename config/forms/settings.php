<?php
return [
    'groups' => [

        'system' => [
            'elements' => [

                'base_url' => [
                    'text',
                    [
                        'label' => __('Site Base URL'),
                        'description' => __('The base URL where this service is located. Use either the external IP address or fully-qualified domain name (if one exists) pointing to this server.'),
                        'default' => $_SERVER['HTTP_HOST'],
                        'filter' => function($str) {
                            return str_replace(['http://', 'https://'], ['', ''], trim($str));
                        },
                        'required' => true,
                    ]
                ],

                'instance_name' => [
                    'text',
                    [
                        'label' => __('AzuraCast Instance Name'),
                        'description' => __('This name will appear as a sub-header next to the AzuraCast logo, to help identify this server.'),
                    ],
                ],

                'timezone' => [
                    'select',
                    [
                        'label' => __('System Default Time Zone'),
                        'description' => __('For users who have not customized their time zone, all times displayed on the site will be based on this time zone.'),
                        'options' => \Azura\Timezone::fetchSelect(),
                        'default' => 'UTC',
                    ],
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

                'history_keep_days' => [
                    'radio',
                    [
                        'label' => __('Days of Playback History to Keep'),
                        'description' => __('Set longer to preserve more playback history for stations. Set shorter to save disk space.'),
                        'choices' => [
                            14 => __('Last 14 Days'),
                            30 => __('Last 30 Days'),
                            60 => __('Last 60 Days'),
                            365 => __('Last Year'),
                            730 => __('Last 2 Years'),
                            0 => __('Indefinitely'),
                        ],
                        'default' => \App\Entity\SongHistory::DEFAULT_DAYS_TO_KEEP,
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
                            \App\Entity\Analytics::LEVEL_ALL => __('<b>Full:</b> Collect aggregate listener statistics and IP-based listener statistics'),
                            \App\Entity\Analytics::LEVEL_NO_IP => __('<b>Limited:</b> Only collect aggregate listener statistics'),
                            \App\Entity\Analytics::LEVEL_NONE => __('<b>None:</b> Do not collect any listener analytics'),
                        ],
                        'default' => \App\Entity\Analytics::LEVEL_ALL,
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
