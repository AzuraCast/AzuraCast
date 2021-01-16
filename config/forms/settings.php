<?php
/**
 * @var App\Environment $settings
 * @var App\Version $version
 */

use App\Entity;

$releaseChannel = $version->getReleaseChannel();
$releaseChannelNames = [
    App\Version::RELEASE_CHANNEL_ROLLING => __('Rolling Release'),
    App\Version::RELEASE_CHANNEL_STABLE => __('Stable'),
];
$releaseChannelName = $releaseChannelNames[$releaseChannel];

return [
    'tabs' => [
        'system' => __('Settings'),
        'security' => __('Security'),
        'privacy' => __('Privacy'),
        'services' => __('Services'),
    ],

    'groups' => [

        'system' => [
            'use_grid' => true,
            'tab' => 'system',

            'elements' => [
                'baseUrl' => [
                    'url',
                    [
                        'label' => __('Site Base URL'),
                        'description' => __(
                            'The base URL where this service is located. Use either the external IP address or fully-qualified domain name (if one exists) pointing to this server.'
                        ),
                        'required' => true,
                        'form_group_class' => 'col-md-6',
                    ],
                ],

                'instanceName' => [
                    'text',
                    [
                        'label' => __('AzuraCast Instance Name'),
                        'description' => __(
                            'This name will appear as a sub-header next to the AzuraCast logo, to help identify this server.'
                        ),
                        'form_group_class' => 'col-md-6',
                    ],
                ],

                'preferBrowserUrl' => [
                    'toggle',
                    [
                        'label' => __('Prefer Browser URL (If Available)'),
                        'description' => __(
                            'If this setting is set to "Yes", the browser URL will be used instead of the base URL when it\'s available. Set to "No" to always use the base URL.'
                        ),
                        'selected_text' => __('Yes'),
                        'deselected_text' => __('No'),
                        'default' => true,
                        'form_group_class' => 'col-md-6',
                    ],
                ],

                'useRadioProxy' => [
                    'toggle',
                    [
                        'label' => __('Use Web Proxy for Radio'),
                        'description' => __(
                            'By default, radio stations broadcast on their own ports (i.e. 8000). If you\'re using a service like CloudFlare or accessing your radio station by SSL, you should enable this feature, which routes all radio through the web ports (80 and 443).'
                        ),
                        'selected_text' => __('Yes'),
                        'deselected_text' => __('No'),
                        'default' => true,
                        'form_group_class' => 'col-md-6',
                    ],
                ],

                'historyKeepDays' => [
                    'radio',
                    [
                        'label' => __('Days of Playback History to Keep'),
                        'description' => __(
                            'Set longer to preserve more playback history and listener metadata for stations. Set shorter to save disk space. '
                        ),
                        'choices' => [
                            14 => __('Last 14 Days'),
                            30 => __('Last 30 Days'),
                            60 => __('Last 60 Days'),
                            365 => __('Last Year'),
                            730 => __('Last 2 Years'),
                            0 => __('Indefinitely'),
                        ],
                        'default' => App\Entity\SongHistory::DEFAULT_DAYS_TO_KEEP,
                        'form_group_class' => 'col-sm-6',
                    ],
                ],

                'enableWebsockets' => [
                    'toggle',
                    [
                        'label' => __('Use WebSockets for Now Playing Updates'),
                        'description' => __(
                            'Enables or disables the use of the newer and faster WebSocket-based system for receiving live updates on public players. You may need to disable this if you encounter problems with it.'
                        ),
                        'selected_text' => __('Yes'),
                        'deselected_text' => __('No'),
                        'default' => false,
                        'form_group_class' => 'col-md-6',
                    ],
                ],

            ],
        ],

        'security' => [
            'use_grid' => true,
            'tab' => 'security',

            'elements' => [

                'alwaysUseSsl' => [
                    'toggle',
                    [
                        'label' => __('Always Use HTTPS'),
                        'description' => __(
                            'Set to "Yes" to always use "https://" secure URLs, and to automatically redirect to the secure URL when an insecure URL is visited.'
                        ),
                        'selected_text' => __('Yes'),
                        'deselected_text' => __('No'),
                        'default' => false,
                        'form_group_class' => 'col-md-6',
                    ],
                ],

                'apiAccessControl' => [
                    'text',
                    [
                        'label' => __('API "Access-Control-Allow-Origin" header'),
                        'class' => 'advanced',
                        'description' => __(
                            '<a href="%s" target="_blank">Learn more about this header</a>. Set to * to allow all sources, or specify a list of origins separated by a comma (,).',
                            'https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Access-Control-Allow-Origin'
                        ),
                        'default' => '',
                        'form_group_class' => 'col-md-12',
                    ],
                ],

            ],
        ],

        'privacy' => [
            'tab' => 'privacy',
            'elements' => [

                'analytics' => [
                    'radio',
                    [
                        'label' => __('Listener Analytics Collection'),
                        'description' => __(
                            'Aggregate listener statistics are used to show station reports across the system. IP-based listener statistics are used to view live listener tracking and may be required for royalty reports.'
                        ),

                        'choices' => [
                            Entity\Analytics::LEVEL_ALL => __(
                                '<b>Full:</b> Collect aggregate listener statistics and IP-based listener statistics'
                            ),
                            Entity\Analytics::LEVEL_NO_IP => __(
                                '<b>Limited:</b> Only collect aggregate listener statistics'
                            ),
                            Entity\Analytics::LEVEL_NONE => __('<b>None:</b> Do not collect any listener analytics'),
                        ],
                        'default' => Entity\Analytics::LEVEL_ALL,
                    ],
                ],
            ],
        ],

        'channels' => [
            'tab' => 'services',
            'legend' => __('AzuraCast Update Checks'),

            'elements' => [

                'release_channel' => [
                    'markup',
                    [
                        'label' => __('Current Release Channel'),
                        'markup' => '<strong>' . $releaseChannelName . '</strong>',
                        'description' => __(
                            'For information on how to switch your release channel, visit <a href="%s" target="_blank">this page</a>.',
                            'https://www.azuracast.com/administration/system/release-channels.html'
                        ),
                    ],
                ],

                'checkForUpdates' => [
                    'toggle',
                    [
                        'label' => __('Show Update Announcements'),
                        'description' => __('Show new releases within your update channel on the AzuraCast homepage.'),
                        'default' => true,
                    ],
                ],

            ],
        ],

        'thirdPartyServices' => [
            'tab' => 'services',
            'use_grid' => true,
            'legend' => __('Third-Party Services'),

            'elements' => [

                'useExternalAlbumArtInApis' => [
                    'toggle',
                    [
                        'label' => __('Check Web Services for Album Art for "Now Playing" Tracks'),
                        'selected_text' => __('Yes'),
                        'deselected_text' => __('No'),
                        'default' => false,
                        'form_group_class' => 'col-md-6',
                    ],
                ],

                'useExternalAlbumArtWhenProcessingMedia' => [
                    'toggle',
                    [
                        'label' => __('Check Web Services for Album Art When Uploading Media'),
                        'selected_text' => __('Yes'),
                        'deselected_text' => __('No'),
                        'default' => false,
                        'form_group_class' => 'col-md-6',
                    ],
                ],

                'lastFmApiKey' => [
                    'text',
                    [
                        'label' => __('Last.fm API Key'),
                        'description' => __(
                            '<a href="%s" target="_blank">Apply for an API key here</a>. This service can provide album art for tracks where none is available locally.',
                            'https://www.last.fm/api/account/create'
                        ),
                        'default' => '',
                        'form_group_class' => 'col-md-12',
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
