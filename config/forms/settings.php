<?php
/** @var App\Settings $settings */

use App\Entity;

return [
    'groups' => [

        'system' => [
            'use_grid' => true,

            'elements' => [
                Entity\Settings::BASE_URL => [
                    'url',
                    [
                        'label' => __('Site Base URL'),
                        'description' => __('The base URL where this service is located. Use either the external IP address or fully-qualified domain name (if one exists) pointing to this server.'),
                        'required' => true,
                        'form_group_class' => 'col-md-6',
                    ],
                ],

                Entity\Settings::INSTANCE_NAME => [
                    'text',
                    [
                        'label' => __('AzuraCast Instance Name'),
                        'description' => __('This name will appear as a sub-header next to the AzuraCast logo, to help identify this server.'),
                        'form_group_class' => 'col-md-6',
                    ],
                ],

                Entity\Settings::PREFER_BROWSER_URL => [
                    'toggle',
                    [
                        'label' => __('Prefer Browser URL (If Available)'),
                        'description' => __('If this setting is set to "Yes", the browser URL will be used instead of the base URL when it\'s available. Set to "No" to always use the base URL.'),
                        'selected_text' => __('Yes'),
                        'deselected_text' => __('No'),
                        'default' => true,
                        'form_group_class' => 'col-md-6',
                    ],
                ],

                Entity\Settings::USE_RADIO_PROXY => [
                    'toggle',
                    [
                        'label' => __('Use Web Proxy for Radio'),
                        'description' => __('By default, radio stations broadcast on their own ports (i.e. 8000). If you\'re using a service like CloudFlare or accessing your radio station by SSL, you should enable this feature, which routes all radio through the web ports (80 and 443).'),
                        'selected_text' => __('Yes'),
                        'deselected_text' => __('No'),
                        'default' => true,
                        'form_group_class' => 'col-md-6',
                    ],
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
                        'form_group_class' => 'col-sm-6',
                    ],
                ],

                Entity\Settings::NOWPLAYING_USE_WEBSOCKETS => [
                    'toggle',
                    [
                        'label' => __('Use WebSockets for Now Playing Updates'),
                        'description' => __('Enables or disables the use of the newer and faster WebSocket-based system for receiving live updates on public players. You may need to disable this if you encounter problems with it.'),
                        'selected_text' => __('Yes'),
                        'deselected_text' => __('No'),
                        'default' => false,
                        'form_group_class' => 'col-md-6',
                    ],
                ],

            ],
        ],

        'security' => [
            'legend' => __('Security Controls'),
            'use_grid' => true,

            'elements' => [

                Entity\Settings::ALWAYS_USE_SSL => [
                    'toggle',
                    [
                        'label' => __('Always Use HTTPS'),
                        'description' => __('Set to "Yes" to always use "https://" secure URLs, and to automatically redirect to the secure URL when an insecure URL is visited.'),
                        'selected_text' => __('Yes'),
                        'deselected_text' => __('No'),
                        'default' => false,
                        'form_group_class' => 'col-md-6',
                    ],
                ],

                Entity\Settings::API_ACCESS_CONTROL => [
                    'text',
                    [
                        'label' => __('API "Access-Control-Allow-Origin" header'),
                        'class' => 'advanced',
                        'description' => __('<a href="%s" target="_blank">Learn more about this header</a>. Set to * to allow all sources, or specify a list of origins separated by a comma (,).',
                            'https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Access-Control-Allow-Origin'),
                        'default' => '',
                        'form_group_class' => 'col-md-12',
                    ],
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
                    ],
                ],
            ],
        ],

        'channels' => [
            'legend' => __('AzuraCast Installation Telemetry'),
            'description' => __('Choose whether your installation communicates with central AzuraCast servers to check for updates and announcements.<br>AzuraCast respects your privacy; see our <a href="%s" target="_blank">privacy policy</a> for more details.',
                'https://www.azuracast.com/privacy.html'),

            'elements' => [

                Entity\Settings::CENTRAL_UPDATES => [
                    'radio',
                    [
                        'label' => __('Check for Updates and Announcements'),
                        'description' => __('Send minimal details about your AzuraCast installation to the AzuraCast central server to check for updated software releases and important announcements.'),

                        'choices' => [
                            Entity\Settings::UPDATES_NONE => __('<b>None:</b> Do not check for updates or announcements.'),
                            Entity\Settings::UPDATES_RELEASE_ONLY => __('<b>Release Only:</b> Critical announcements and new release versions only.'),
                            Entity\Settings::UPDATES_ALL => __('<b>All Updates:</b> Include all announcements and minor updates.'),
                        ],
                        'default' => Entity\Settings::UPDATES_RELEASE_ONLY,
                    ],
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
                    ],
                ],
            ],
        ],
    ],
];