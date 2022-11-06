<?php
/**
 * Administrative dashboard configuration.
 */

use App\Enums\StationFeatures;
use App\Enums\StationPermissions;
use App\Radio\Enums\AudioProcessingMethods;

return static function (App\Event\BuildStationMenu $e) {
    $request = $e->getRequest();
    $station = $e->getStation();

    $backendConfig = $station->getBackendConfig();

    $router = $request->getRouter();
    $frontendEnum = $station->getFrontendTypeEnum();

    $willDisconnectMessage = __('Restart broadcasting? This will disconnect any current listeners.');
    $willNotDisconnectMessage = __('Reload broadcasting? Current listeners will not be disconnected.');

    $reloadSupported = $frontendEnum->supportsReload();
    $reloadMessage = $reloadSupported ? $willNotDisconnectMessage : $willDisconnectMessage;

    $settings = $e->getSettings();

    $hasLocalServices = $station->hasLocalServices();

    $e->merge(
        [
            'start_station' => [
                'label' => __('Start Station'),
                'title' => __('Ready to start broadcasting? Click to start your station.'),
                'icon' => 'refresh',
                'url' => $router->fromHere('api:stations:reload'),
                'class' => 'api-call text-success',
                'confirm' => $reloadMessage,
                'visible' => $hasLocalServices && !$station->getHasStarted(),
                'permission' => StationPermissions::Broadcasting,
            ],
            'restart_station' => [
                'label' => __('Reload to Apply Changes'),
                'title' => __('Click to restart your station and apply configuration changes.'),
                'icon' => 'refresh',
                'url' => $router->fromHere('api:stations:reload'),
                'class' => 'api-call text-warning btn-restart-station '
                    . (!$station->getNeedsRestart() ? 'd-none' : ''),
                'confirm' => $reloadMessage,
                'visible' => $hasLocalServices && $station->getHasStarted(),
                'permission' => StationPermissions::Broadcasting,
            ],
            'profile' => [
                'label' => __('Profile'),
                'icon' => 'image',
                'url' => $router->fromHere('stations:profile:index'),
            ],
            'public' => [
                'label' => __('Public Page'),
                'icon' => 'public',
                'url' => $router->named('public:index', ['station_id' => $station->getShortName()]),
                'external' => true,
                'visible' => $station->getEnablePublicPage(),
            ],
            'media' => [
                'label' => __('Media'),
                'icon' => 'library_music',
                'visible' => StationFeatures::Media->supportedForStation($station),
                'items' => [
                    'files' => [
                        'label' => __('Music Files'),
                        'icon' => 'library_music',
                        'url' => $router->fromHere('stations:files:index'),
                        'permission' => StationPermissions::Media,
                    ],
                    'reports_duplicates' => [
                        'label' => __('Duplicate Songs'),
                        'class' => 'text-muted',
                        'url' => $router->fromHere('stations:files:index') . '#special:duplicates',
                        'permission' => StationPermissions::Media,
                    ],
                    'reports_unprocessable' => [
                        'label' => __('Unprocessable Files'),
                        'class' => 'text-muted',
                        'url' => $router->fromHere('stations:files:index') . '#special:unprocessable',
                        'permission' => StationPermissions::Media,
                    ],
                    'reports_unassigned' => [
                        'label' => __('Unassigned Files'),
                        'class' => 'text-muted',
                        'url' => $router->fromHere('stations:files:index') . '#special:unassigned',
                        'permission' => StationPermissions::Media,
                    ],
                    'ondemand' => [
                        'label' => __('On-Demand Media'),
                        'class' => 'text-muted',
                        'icon' => 'cloud_download',
                        'url' => $router->named('public:ondemand', ['station_id' => $station->getShortName()]),
                        'external' => true,
                        'visible' => $station->getEnableOnDemand(),
                    ],
                    'sftp_users' => [
                        'label' => __('SFTP Users'),
                        'class' => 'text-muted',
                        'url' => $router->fromHere('stations:sftp_users:index'),
                        'visible' => StationFeatures::Sftp->supportedForStation($station),
                        'permission' => StationPermissions::Media,
                    ],
                    'bulk_media' => [
                        'label' => __('Bulk Media Import/Export'),
                        'class' => 'text-muted',
                        'url' => $router->fromHere('stations:bulk-media'),
                        'permission' => StationPermissions::Media,
                    ],
                ],
            ],

            'playlists' => [
                'label' => __('Playlists'),
                'icon' => 'queue_music',
                'url' => $router->fromHere('stations:playlists:index'),
                'visible' => StationFeatures::Media->supportedForStation($station),
                'permission' => StationPermissions::Media,
            ],

            'podcasts' => [
                'label' => __('Podcasts'),
                'icon' => 'cast',
                'url' => $router->fromHere('stations:podcasts:index'),
                'visible' => StationFeatures::Podcasts->supportedForStation($station),
                'permission' => StationPermissions::Podcasts,
            ],

            'live_streaming' => [
                'label' => __('Live Streaming'),
                'icon' => 'mic',
                'visible' => StationFeatures::Streamers->supportedForStation($station),
                'items' => [
                    'streamers' => [
                        'label' => __('Streamer/DJ Accounts'),
                        'icon' => 'mic',
                        'url' => $router->fromHere('stations:streamers:index'),
                        'permission' => StationPermissions::Streamers,
                    ],

                    'web_dj' => [
                        'label' => __('Web DJ'),
                        'icon' => 'surround_sound',
                        'url' => (string)(
                        $router->namedAsUri(
                            'public:dj',
                            ['station_id' => $station->getShortName()],
                            [],
                            true
                        )->withScheme('https')
                        ),
                        'visible' => $station->getEnablePublicPage(),
                        'external' => true,
                    ],
                ],
            ],

            'webhooks' => [
                'label' => __('Web Hooks'),
                'icon' => 'code',
                'url' => $router->fromHere('stations:webhooks:index'),
                'visible' => StationFeatures::Webhooks->supportedForStation($station),
                'permission' => StationPermissions::WebHooks,
            ],

            'reports' => [
                'label' => __('Reports'),
                'icon' => 'assignment',
                'permission' => StationPermissions::Reports,
                'items' => [
                    'reports_overview' => [
                        'label' => __('Station Statistics'),
                        'url' => $router->fromHere('stations:reports:overview'),
                    ],
                    'reports_listeners' => [
                        'label' => __('Listeners'),
                        'url' => $router->fromHere('stations:reports:listeners'),
                    ],
                    'reports_requests' => [
                        'label' => __('Song Requests'),
                        'url' => $router->fromHere('stations:reports:requests'),
                        'visible' => $station->getEnableRequests(),
                    ],
                    'reports_timeline' => [
                        'label' => __('Song Playback Timeline'),
                        'url' => $router->fromHere('stations:reports:timeline'),
                    ],
                    'reports_soundexchange' => [
                        'label' => __('SoundExchange Royalties'),
                        'url' => $router->fromHere('stations:reports:soundexchange'),
                    ],
                ],
            ],

            'broadcasting' => [
                'label' => __('Broadcasting'),
                'icon' => 'wifi_tethering',
                'items' => [
                    'mounts' => [
                        'label' => __('Mount Points'),
                        'icon' => 'wifi_tethering',
                        'url' => $router->fromHere('stations:mounts:index'),
                        'visible' => StationFeatures::MountPoints->supportedForStation($station),
                        'permission' => StationPermissions::MountPoints,
                    ],
                    'hls_streams' => [
                        'label' => __('HLS Streams'),
                        'url' => $router->fromHere('stations:hls_streams:index'),
                        'visible' => StationFeatures::HlsStreams->supportedForStation($station),
                        'permission' => StationPermissions::MountPoints,
                    ],
                    'remotes' => [
                        'label' => __('Remote Relays'),
                        'icon' => 'router',
                        'url' => $router->fromHere('stations:remotes:index'),
                        'visible' => StationFeatures::RemoteRelays->supportedForStation($station),
                        'permission' => StationPermissions::RemoteRelays,
                    ],
                    'fallback' => [
                        'label' => __('Custom Fallback File'),
                        'class' => 'text-muted',
                        'url' => $router->fromHere('stations:fallback'),
                        'visible' => StationFeatures::Media->supportedForStation($station),
                        'permission' => StationPermissions::Broadcasting,
                    ],
                    'ls_config' => [
                        'label' => __('Edit Liquidsoap Configuration'),
                        'class' => 'text-muted',
                        'url' => $router->fromHere('stations:util:ls_config'),
                        'visible' => StationFeatures::CustomLiquidsoapConfig->supportedForStation($station),
                        'permission' => StationPermissions::Broadcasting,
                    ],
                    'stations:stereo_tool_config' => [
                        'label' => __('Upload Stereo Tool Configuration'),
                        'class' => 'text-muted',
                        'url' => $router->fromHere('stations:stereo_tool_config'),
                        'visible' => $settings->getEnableAdvancedFeatures()
                            && StationFeatures::Media->supportedForStation($station)
                            && AudioProcessingMethods::StereoTool === $backendConfig->getAudioProcessingMethodEnum(),
                        'permission' => StationPermissions::Broadcasting,
                    ],
                    'queue' => [
                        'label' => __('Upcoming Song Queue'),
                        'class' => 'text-muted',
                        'url' => $router->fromHere('stations:queue:index'),
                        'permission' => StationPermissions::Broadcasting,
                        'visible' => $station->supportsAutoDjQueue(),
                    ],
                    'reload' => [
                        'label' => __('Reload Configuration'),
                        'class' => 'text-muted api-call',
                        'url' => $router->fromHere('api:stations:reload'),
                        'confirm' => $willNotDisconnectMessage,
                        'permission' => StationPermissions::Broadcasting,
                        'visible' => $hasLocalServices && $reloadSupported,
                    ],
                    'restart' => [
                        'label' => __('Restart Broadcasting'),
                        'class' => 'text-muted api-call',
                        'url' => $router->fromHere('api:stations:restart'),
                        'confirm' => $willDisconnectMessage,
                        'permission' => StationPermissions::Broadcasting,
                        'visible' => $hasLocalServices,
                    ],
                ],
            ],

            'logs' => [
                'label' => __('Logs'),
                'icon' => 'web_stories',
                'url' => $router->fromHere('stations:logs'),
                'permission' => StationPermissions::Logs,
            ],
        ]
    );
};
