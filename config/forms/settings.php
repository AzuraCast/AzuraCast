<?php
use App\Entity;

return [
    'groups' => [

        'system' => [
            'elements' => [
                Entity\Settings::BASE_URL => [
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

                Entity\Settings::INSTANCE_NAME => [
                    'text',
                    [
                        'label' => __('AzuraCast Instance Name'),
                        'description' => __('This name will appear as a sub-header next to the AzuraCast logo, to help identify this server.'),
                    ],
                ],

                Entity\Settings::TIMEZONE => [
                    'select',
                    [
                        'label' => __('System Default Time Zone'),
                        'description' => __('For users who have not customized their time zone, all times displayed on the site will be based on this time zone.'),
                        'options' => \Azura\Timezone::fetchSelect(),
                        'default' => 'UTC',
                    ],
                ],

                Entity\Settings::PREFER_BROWSER_URL => [
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

                Entity\Settings::ALWAYS_USE_SSL => [
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

                Entity\Settings::USE_RADIO_PROXY => [
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

                Entity\Settings::HISTORY_KEEP_DAYS => [
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
            'description' => __('AzuraCast does not send your station or listener data to any external server. You can control how much data AzuraCast logs about your listeners here.'),

            'elements' => [

                Entity\Settings::LISTENER_ANALYTICS => [
                    'radio',
                    [
                        'label' => __('Listener Analytics Collection'),
                        'description' => __('Aggregate listener statistics are used to show station reports across the system. IP-based listener statistics are used to view live listener tracking and may be required for royalty reports.'),

                        'choices' => [
                            Entity\Analytics::LEVEL_ALL => __('<b>Full:</b> Collect aggregate listener statistics and IP-based listener statistics'),
                            Entity\Analytics::LEVEL_NO_IP => __('<b>Limited:</b> Only collect aggregate listener statistics'),
                            Entity\Analytics::LEVEL_NONE => __('<b>None:</b> Do not collect any listener analytics'),
                        ],
                        'default' => Entity\Analytics::LEVEL_ALL,
                    ]
                ],
            ],
        ],

        'channels' => [
            'legend' => __('AzuraCast Installation Telemetry'),
            'description' => __('Choose whether your installation communicates with central AzuraCast servers to check for updates and announcements.<br>AzuraCast respects your privacy; see our <a href="%s" target="_blank">privacy policy</a> for more details.', 'https://www.azuracast.com/privacy.html'),

            'elements' => [

                Entity\Settings::CENTRAL_UPDATES => [
                    'radio',
                    [
                        'label' => __('Check for Updates and Announcements'),
                        'description' => __('Send minimal details about your AzuraCast installation to the AzuraCast central server to check for updated software releases and important announcements.'),

                        'choices' => [
                            0 => __('No'),
                            1 => __('Yes'),
                        ],
                        'default' => 1,
                    ]
                ]

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
