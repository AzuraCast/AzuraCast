<?php
/**
 * Administrative dashboard configuration.
 */

use App\Acl;

return function (App\Event\BuildStationMenu $e) {
    $request = $e->getRequest();
    $station = $e->getStation();

    $router = $request->getRouter();
    $backend = $request->getStationBackend();
    $frontend = $request->getStationFrontend();

    $settings = $e->getSettings();

    $e->merge(
        [
            'start_station' => [
                'label' => __('Start Station'),
                'title' => __('Ready to start broadcasting? Click to start your station.'),
                'icon' => 'refresh',
                'url' => (string)$router->fromHere('api:stations:restart'),
                'class' => 'api-call text-success',
                'confirm' => __('Restart broadcasting? This will disconnect any current listeners.'),
                'visible' => !$station->getHasStarted(),
                'permission' => Acl::STATION_BROADCASTING,
            ],
            'restart_station' => [
                'label' => __('Restart to Apply Changes'),
                'title' => __('Click to restart your station and apply configuration changes.'),
                'icon' => 'refresh',
                'url' => (string)$router->fromHere('api:stations:restart'),
                'class' => 'api-call text-warning',
                'confirm' => __('Restart broadcasting? This will disconnect any current listeners.'),
                'visible' => $station->getHasStarted() && $station->getNeedsRestart(),
                'permission' => Acl::STATION_BROADCASTING,
            ],
            'profile' => [
                'label' => __('Profile'),
                'icon' => 'image',
                'url' => (string)$router->fromHere('stations:profile:index'),
            ],
            'public' => [
                'label' => __('Public Page'),
                'icon' => 'public',
                'url' => (string)$router->named('public:index', ['station_id' => $station->getShortName()]),
                'external' => true,
                'visible' => $station->getEnablePublicPage(),
            ],
            'ondemand' => [
                'label' => __('On-Demand Media'),
                'icon' => 'cloud_download',
                'url' => (string)$router->named('public:ondemand', ['station_id' => $station->getShortName()]),
                'external' => true,
                'visible' => $station->getEnableOnDemand(),
            ],
            'files' => [
                'label' => __('Music Files'),
                'icon' => 'library_music',
                'url' => (string)$router->fromHere('stations:files:index'),
                'visible' => $backend->supportsMedia(),
                'permission' => Acl::STATION_MEDIA,
            ],
            'playlists' => [
                'label' => __('Playlists'),
                'icon' => 'queue_music',
                'url' => (string)$router->fromHere('stations:playlists:index'),
                'visible' => $backend->supportsMedia(),
                'permission' => Acl::STATION_MEDIA,
            ],
            'podcasts' => [
                'label' => __('Podcasts (Beta)'),
                'icon' => 'cast',
                'url' => (string)$router->fromHere('stations:podcasts:index'),
                'permission' => Acl::STATION_PODCASTS,
            ],
            'streamers' => [
                'label' => __('Streamer/DJ Accounts'),
                'icon' => 'mic',
                'url' => (string)$router->fromHere('stations:streamers:index'),
                'visible' => $backend->supportsStreamers(),
                'permission' => Acl::STATION_STREAMERS,
            ],
            'web_dj' => [
                'label' => __('Web DJ'),
                'icon' => 'surround_sound',
                'url' => (string)$router->named('public:dj', ['station_id' => $station->getShortName()], [], true)
                    ->withScheme('https'),
                'visible' => $station->getEnablePublicPage() && $station->getEnableStreamers(),
                'external' => true,
            ],
            'mounts' => [
                'label' => __('Mount Points'),
                'icon' => 'wifi_tethering',
                'url' => (string)$router->fromHere('stations:mounts:index'),
                'visible' => $frontend->supportsMounts(),
                'permission' => Acl::STATION_MOUNTS,
            ],
            'remotes' => [
                'label' => __('Remote Relays'),
                'icon' => 'router',
                'url' => (string)$router->fromHere('stations:remotes:index'),
                'permission' => Acl::STATION_REMOTES,
            ],
            'webhooks' => [
                'label' => __('Web Hooks'),
                'icon' => 'code',
                'url' => (string)$router->fromHere('stations:webhooks:index'),
                'permission' => Acl::STATION_WEB_HOOKS,
            ],
            'reports' => [
                'label' => __('Reports'),
                'icon' => 'assignment',
                'permission' => Acl::STATION_REPORTS,
                'items' => [
                    'reports_overview' => [
                        'label' => __('Statistics Overview'),
                        'url' => (string)$router->fromHere('stations:reports:overview'),
                    ],
                    'reports_listeners' => [
                        'label' => __('Listeners'),
                        'url' => (string)$router->fromHere('stations:reports:listeners'),
                        'visible' => $frontend->supportsListenerDetail(),
                    ],
                    'reports_requests' => [
                        'label' => __('Song Requests'),
                        'url' => (string)$router->fromHere('stations:reports:requests'),
                        'visible' => $station->getEnableRequests(),
                    ],
                    'reports_timeline' => [
                        'label' => __('Song Playback Timeline'),
                        'url' => (string)$router->fromHere('stations:reports:timeline'),
                    ],
                    'reports_performance' => [
                        'label' => __('Song Listener Impact'),
                        'url' => (string)$router->fromHere('stations:reports:performance'),
                        'visible' => $backend->supportsMedia(),
                    ],
                    'reports_duplicates' => [
                        'label' => __('Duplicate Songs'),
                        'url' => (string)$router->fromHere('stations:files:index') . '#special:duplicates',
                        'visible' => $backend->supportsMedia(),
                    ],
                    'reports_unprocessable' => [
                        'label' => __('Unprocessable Files'),
                        'url' => (string)$router->fromHere('stations:files:index') . '#special:unprocessable',
                        'visible' => $backend->supportsMedia(),
                    ],
                    'reports_soundexchange' => [
                        'label' => __('SoundExchange Royalties'),
                        'url' => (string)$router->fromHere('stations:reports:soundexchange'),
                        'visible' => $frontend->supportsListenerDetail(),
                    ],
                ],
            ],
            'utilities' => [
                'label' => __('Utilities'),
                'icon' => 'settings',
                'items' => [
                    'sftp_users' => [
                        'label' => __('SFTP Users'),
                        'url' => (string)$router->fromHere('stations:sftp_users:index'),
                        'visible' => App\Service\SftpGo::isSupportedForStation($station),
                        'permission' => Acl::STATION_MEDIA,
                    ],
                    'automation' => [
                        'label' => __('Automated Assignment'),
                        'url' => (string)$router->fromHere('stations:automation:index'),
                        'visible' => $backend->supportsMedia(),
                        'permission' => Acl::STATION_AUTOMATION,
                    ],
                    'ls_config' => [
                        'label' => __('Edit Liquidsoap Configuration'),
                        'url' => (string)$router->fromHere('stations:util:ls_config'),
                        'visible' => $settings->getEnableAdvancedFeatures()
                            && $backend instanceof App\Radio\Backend\Liquidsoap,
                        'permission' => Acl::STATION_BROADCASTING,
                    ],
                    'logs' => [
                        'label' => __('Log Viewer'),
                        'url' => (string)$router->fromHere('stations:logs:index'),
                        'permission' => Acl::STATION_LOGS,
                    ],
                    'queue' => [
                        'label' => __('Upcoming Song Queue'),
                        'url' => (string)$router->fromHere('stations:queue:index'),
                        'permission' => Acl::STATION_BROADCASTING,
                    ],
                    'restart' => [
                        'label' => __('Restart Broadcasting'),
                        'url' => (string)$router->fromHere('api:stations:restart'),
                        'class' => 'api-call',
                        'confirm' => __('Restart broadcasting? This will disconnect any current listeners.'),
                        'permission' => Acl::STATION_BROADCASTING,
                    ],
                ],
            ],
        ]
    );
};
