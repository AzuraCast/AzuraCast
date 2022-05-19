<?php

use App\Controller;
use App\Enums\StationPermissions;
use App\Middleware;
use Slim\Routing\RouteCollectorProxy;

return static function (RouteCollectorProxy $group) {
    $group->group(
        '/station/{station_id}',
        function (RouteCollectorProxy $group) {
            $group->get('', Controller\Api\Stations\IndexController::class . ':indexAction')
                ->setName('api:stations:index')
                ->add(new Middleware\RateLimit('api', 5, 2));

            $group->group(
                '/automation',
                function (RouteCollectorProxy $group) {
                    $group->get(
                        '/settings',
                        Controller\Api\Stations\Automation\GetSettingsAction::class
                    )->setName('api:stations:automation:settings');

                    $group->put(
                        '/settings',
                        Controller\Api\Stations\Automation\PutSettingsAction::class
                    );

                    $group->put(
                        '/run',
                        Controller\Api\Stations\Automation\RunAction::class
                    )->setName('api:stations:automation:run');
                }
            )->add(new Middleware\Permissions(StationPermissions::Automation, true));

            $group->get('/nowplaying', Controller\Api\NowPlayingAction::class . ':indexAction');

            $group->map(
                ['GET', 'POST'],
                '/nowplaying/update',
                Controller\Api\Stations\UpdateMetadataAction::class
            )
                ->add(new Middleware\Permissions(StationPermissions::Broadcasting, true));

            $group->get('/profile', Controller\Api\Stations\ProfileAction::class)
                ->setName('api:stations:profile')
                ->add(new Middleware\Permissions(StationPermissions::View, true));

            $group->get(
                '/profile/edit',
                Controller\Api\Stations\ProfileEditController::class . ':getProfileAction'
            )->setName('api:stations:profile:edit')
                ->add(new Middleware\Permissions(StationPermissions::Profile, true));

            $group->put(
                '/profile/edit',
                Controller\Api\Stations\ProfileEditController::class . ':putProfileAction'
            )->add(new Middleware\Permissions(StationPermissions::Profile, true));

            $group->get('/quota[/{type}]', Controller\Api\Stations\GetQuotaAction::class)
                ->setName('api:stations:quota')
                ->add(new Middleware\Permissions(StationPermissions::View, true));

            $group->get('/schedule', Controller\Api\Stations\ScheduleAction::class)
                ->setName('api:stations:schedule');

            $group->get('/history', Controller\Api\Stations\HistoryController::class)
                ->setName('api:stations:history')
                ->add(new Middleware\Permissions(StationPermissions::Reports, true));

            $group->group(
                '/queue',
                function (RouteCollectorProxy $group) {
                    $group->get('', Controller\Api\Stations\QueueController::class . ':listAction')
                        ->setName('api:stations:queue');

                    $group->post('/clear', Controller\Api\Stations\QueueController::class . ':clearAction')
                        ->setName('api:stations:queue:clear');

                    $group->delete('/{id}', Controller\Api\Stations\QueueController::class . ':deleteAction')
                        ->setName('api:stations:queue:record');
                }
            )->add(new Middleware\Permissions(StationPermissions::Broadcasting, true));

            $group->get('/requests', Controller\Api\Stations\RequestsController::class . ':listAction')
                ->setName('api:requests:list');

            $group->map(
                ['GET', 'POST'],
                '/request/{media_id}',
                Controller\Api\Stations\RequestsController::class . ':submitAction'
            )
                ->setName('api:requests:submit')
                ->add(new Middleware\RateLimit('api', 5, 2));

            $group->get('/ondemand', Controller\Api\Stations\OnDemand\ListAction::class)
                ->setName('api:stations:ondemand:list');

            $group->get('/ondemand/download/{media_id}', Controller\Api\Stations\OnDemand\DownloadAction::class)
                ->setName('api:stations:ondemand:download')
                ->add(new Middleware\RateLimit('ondemand', 1, 2));

            $group->get('/listeners', Controller\Api\Stations\ListenersAction::class)
                ->setName('api:listeners:index')
                ->add(new Middleware\Permissions(StationPermissions::Reports, true));

            $group->get(
                '/waveform/{media_id:[a-zA-Z0-9\-]+}.json',
                Controller\Api\Stations\Waveform\GetWaveformAction::class
            )
                ->setName('api:stations:media:waveform');

            $group->get('/art/{media_id:[a-zA-Z0-9\-]+}.jpg', Controller\Api\Stations\Art\GetArtAction::class)
                ->setName('api:stations:media:art');

            $group->get('/art/{media_id:[a-zA-Z0-9\-]+}', Controller\Api\Stations\Art\GetArtAction::class)
                ->setName('api:stations:media:art-internal');

            $group->post('/art/{media_id:[a-zA-Z0-9]+}', Controller\Api\Stations\Art\PostArtAction::class)
                ->add(new Middleware\Permissions(StationPermissions::Media, true));

            $group->delete('/art/{media_id:[a-zA-Z0-9]+}', Controller\Api\Stations\Art\DeleteArtAction::class)
                ->add(new Middleware\Permissions(StationPermissions::Media, true));

            $group->group(
                '/liquidsoap-config',
                function (RouteCollectorProxy $group) {
                    $group->get(
                        '',
                        Controller\Api\Stations\LiquidsoapConfig\GetAction::class
                    )->setName('api:stations:liquidsoap-config');

                    $group->put(
                        '',
                        Controller\Api\Stations\LiquidsoapConfig\PutAction::class
                    );
                }
            )->add(new Middleware\Permissions(StationPermissions::Broadcasting, true));

            // Public and private podcast pages
            $group->group(
                '/podcast/{podcast_id}',
                function (RouteCollectorProxy $group) {
                    $group->get('', Controller\Api\Stations\PodcastsController::class . ':getAction')
                        ->setName('api:stations:podcast');

                    $group->get(
                        '/art',
                        Controller\Api\Stations\Podcasts\Art\GetArtAction::class
                    )->setName('api:stations:podcast:art');

                    $group->get(
                        '/episodes',
                        Controller\Api\Stations\PodcastEpisodesController::class . ':listAction'
                    )->setName('api:stations:podcast:episodes');

                    $group->group(
                        '/episode/{episode_id}',
                        function (RouteCollectorProxy $group) {
                            $group->get(
                                '',
                                Controller\Api\Stations\PodcastEpisodesController::class . ':getAction'
                            )->setName('api:stations:podcast:episode');

                            $group->get(
                                '/art',
                                Controller\Api\Stations\Podcasts\Episodes\Art\GetArtAction::class
                            )->setName('api:stations:podcast:episode:art');

                            $group->get(
                                '/download',
                                Controller\Api\Stations\Podcasts\Episodes\Media\GetMediaAction::class
                            )->setName('api:stations:podcast:episode:download');
                        }
                    );
                }
            )->add(Middleware\RequirePublishedPodcastEpisodeMiddleware::class);

            // Private-only podcast pages
            $group->group(
                '/podcasts',
                function (RouteCollectorProxy $group) {
                    $group->get('', Controller\Api\Stations\PodcastsController::class . ':listAction')
                        ->setName('api:stations:podcasts');

                    $group->post('', Controller\Api\Stations\PodcastsController::class . ':createAction');

                    $group->post('/art', Controller\Api\Stations\Podcasts\Art\PostArtAction::class)
                        ->setName('api:stations:podcasts:new-art');
                }
            )->add(new Middleware\Permissions(StationPermissions::Podcasts, true));

            $group->group(
                '/podcast/{podcast_id}',
                function (RouteCollectorProxy $group) {
                    $group->put('', Controller\Api\Stations\PodcastsController::class . ':editAction');

                    $group->delete('', Controller\Api\Stations\PodcastsController::class . ':deleteAction');

                    $group->post(
                        '/art',
                        Controller\Api\Stations\Podcasts\Art\PostArtAction::class
                    )->setName('api:stations:podcast:art-internal');

                    $group->delete(
                        '/art',
                        Controller\Api\Stations\Podcasts\Art\DeleteArtAction::class
                    );

                    $group->post(
                        '/episodes',
                        Controller\Api\Stations\PodcastEpisodesController::class . ':createAction'
                    );

                    $group->post(
                        '/episodes/art',
                        Controller\Api\Stations\Podcasts\Episodes\Art\PostArtAction::class
                    )->setName('api:stations:podcast:episodes:new-art');

                    $group->post(
                        '/episodes/media',
                        Controller\Api\Stations\Podcasts\Episodes\Media\PostMediaAction::class
                    )->setName('api:stations:podcast:episodes:new-media');

                    $group->group(
                        '/episode/{episode_id}',
                        function (RouteCollectorProxy $group) {
                            $group->put(
                                '',
                                Controller\Api\Stations\PodcastEpisodesController::class . ':editAction'
                            );

                            $group->delete(
                                '',
                                Controller\Api\Stations\PodcastEpisodesController::class . ':deleteAction'
                            );

                            $group->post(
                                '/art',
                                Controller\Api\Stations\Podcasts\Episodes\Art\PostArtAction::class
                            )->setName('api:stations:podcast:episode:art-internal');

                            $group->delete(
                                '/art',
                                Controller\Api\Stations\Podcasts\Episodes\Art\DeleteArtAction::class
                            );

                            $group->post(
                                '/media',
                                Controller\Api\Stations\Podcasts\Episodes\Media\PostMediaAction::class
                            )->setName('api:stations:podcast:episode:media-internal');

                            $group->delete(
                                '/media',
                                Controller\Api\Stations\Podcasts\Episodes\Media\DeleteMediaAction::class
                            );
                        }
                    );
                }
            )->add(new Middleware\Permissions(StationPermissions::Podcasts, true));

            $station_api_endpoints = [
                [
                    'file',
                    'files',
                    Controller\Api\Stations\FilesController::class,
                    StationPermissions::Media,
                ],
                [
                    'mount',
                    'mounts',
                    Controller\Api\Stations\MountsController::class,
                    StationPermissions::MountPoints,
                ],
                [
                    'playlist',
                    'playlists',
                    Controller\Api\Stations\PlaylistsController::class,
                    StationPermissions::Media,
                ],
                [
                    'remote',
                    'remotes',
                    Controller\Api\Stations\RemotesController::class,
                    StationPermissions::RemoteRelays,
                ],
                [
                    'sftp-user',
                    'sftp-users',
                    Controller\Api\Stations\SftpUsersController::class,
                    StationPermissions::Media,
                ],
                [
                    'streamer',
                    'streamers',
                    Controller\Api\Stations\StreamersController::class,
                    StationPermissions::Streamers,
                ],
                [
                    'webhook',
                    'webhooks',
                    Controller\Api\Stations\WebhooksController::class,
                    StationPermissions::WebHooks,
                ],
            ];

            foreach ($station_api_endpoints as [$singular, $plural, $class, $permission]) {
                $group->group(
                    '',
                    function (RouteCollectorProxy $group) use ($singular, $plural, $class) {
                        $group->get('/' . $plural, $class . ':listAction')
                            ->setName('api:stations:' . $plural);
                        $group->post('/' . $plural, $class . ':createAction');

                        $group->get('/' . $singular . '/{id}', $class . ':getAction')
                            ->setName('api:stations:' . $singular);
                        $group->put('/' . $singular . '/{id}', $class . ':editAction');
                        $group->delete('/' . $singular . '/{id}', $class . ':deleteAction');
                    }
                )->add(new Middleware\Permissions($permission, true));
            }

            $group->group(
                '/files',
                function (RouteCollectorProxy $group) {
                    $group->get('/list', Controller\Api\Stations\Files\ListAction::class)
                        ->setName('api:stations:files:list');

                    $group->get('/directories', Controller\Api\Stations\Files\ListDirectoriesAction::class)
                        ->setName('api:stations:files:directories');

                    $group->put('/rename', Controller\Api\Stations\Files\RenameAction::class)
                        ->setName('api:stations:files:rename');

                    $group->put('/batch', Controller\Api\Stations\Files\BatchAction::class)
                        ->setName('api:stations:files:batch');

                    $group->post('/mkdir', Controller\Api\Stations\Files\MakeDirectoryAction::class)
                        ->setName('api:stations:files:mkdir');

                    $group->get('/play/{id}', Controller\Api\Stations\Files\PlayAction::class)
                        ->setName('api:stations:files:play');

                    $group->get('/download', Controller\Api\Stations\Files\DownloadAction::class)
                        ->setName('api:stations:files:download');

                    $group->get('/bulk', Controller\Api\Stations\Files\BulkDownloadAction::class)
                        ->setName('api:stations:files:bulk');

                    $group->post('/bulk', Controller\Api\Stations\Files\BulkUploadAction::class);

                    $group->map(
                        ['GET', 'POST'],
                        '/upload',
                        Controller\Api\Stations\Files\FlowUploadAction::class
                    )->setName('api:stations:files:upload');
                }
            )
                ->add(Middleware\Module\StationFiles::class)
                ->add(new Middleware\Permissions(StationPermissions::Media, true));

            $group->post(
                '/mounts/intro',
                Controller\Api\Stations\Mounts\Intro\PostIntroAction::class
            )->setName('api:stations:mounts:new-intro')
                ->add(new Middleware\Permissions(StationPermissions::MountPoints, true));

            $group->group(
                '/mount/{id}',
                function (RouteCollectorProxy $group) {
                    $group->get(
                        '/intro',
                        Controller\Api\Stations\Mounts\Intro\GetIntroAction::class
                    )->setName('api:stations:mounts:intro');

                    $group->post(
                        '/intro',
                        Controller\Api\Stations\Mounts\Intro\PostIntroAction::class
                    );

                    $group->delete(
                        '/intro',
                        Controller\Api\Stations\Mounts\Intro\DeleteIntroAction::class
                    );
                }
            )->add(new Middleware\Permissions(StationPermissions::MountPoints, true));

            $group->get(
                '/playlists/schedule',
                Controller\Api\Stations\PlaylistsController::class . ':scheduleAction'
            )
                ->setName('api:stations:playlists:schedule')
                ->add(new Middleware\Permissions(StationPermissions::Media, true));

            $group->group(
                '/playlist/{id}',
                function (RouteCollectorProxy $group) {
                    $group->put(
                        '/toggle',
                        Controller\Api\Stations\Playlists\ToggleAction::class
                    )->setName('api:stations:playlist:toggle');

                    $group->put(
                        '/reshuffle',
                        Controller\Api\Stations\Playlists\ReshuffleAction::class
                    )->setName('api:stations:playlist:reshuffle');

                    $group->get(
                        '/order',
                        Controller\Api\Stations\Playlists\GetOrderAction::class
                    )->setName('api:stations:playlist:order');

                    $group->put(
                        '/order',
                        Controller\Api\Stations\Playlists\PutOrderAction::class
                    );

                    $group->get(
                        '/queue',
                        Controller\Api\Stations\Playlists\GetQueueAction::class
                    )->setName('api:stations:playlist:queue');

                    $group->delete(
                        '/queue',
                        Controller\Api\Stations\Playlists\DeleteQueueAction::class
                    );

                    $group->post(
                        '/clone',
                        Controller\Api\Stations\Playlists\CloneAction::class
                    )->setName('api:stations:playlist:clone');

                    $group->post(
                        '/import',
                        Controller\Api\Stations\Playlists\ImportAction::class
                    )->setName('api:stations:playlist:import');

                    $group->get(
                        '/export[/{format}]',
                        Controller\Api\Stations\Playlists\ExportAction::class
                    )->setName('api:stations:playlist:export');
                }
            )->add(new Middleware\Permissions(StationPermissions::Media, true));

            $group->group(
                '/reports',
                function (RouteCollectorProxy $group) {
                    $group->group(
                        '/requests',
                        function (RouteCollectorProxy $group) {
                            $group->get(
                                '',
                                Controller\Api\Stations\Reports\RequestsController::class . ':listAction'
                            )->setName('api:stations:reports:requests');

                            $group->post(
                                '/clear',
                                Controller\Api\Stations\Reports\RequestsController::class . ':clearAction'
                            )->setName('api:stations:reports:requests:clear');

                            $group->delete(
                                '/{request_id}',
                                Controller\Api\Stations\Reports\RequestsController::class . ':deleteAction'
                            )->setName('api:stations:reports:requests:delete');
                        }
                    )->add(new Middleware\Permissions(StationPermissions::Broadcasting, true));

                    $group->get(
                        '/performance',
                        Controller\Api\Stations\Reports\PerformanceAction::class
                    )->setName('api:stations:reports:performance');

                    $group->get(
                        '/overview/charts',
                        Controller\Api\Stations\Reports\Overview\ChartsAction::class
                    )->setName('api:stations:reports:overview-charts');

                    $group->get(
                        '/overview/best-and-worst',
                        Controller\Api\Stations\Reports\Overview\BestAndWorstAction::class
                    )->setName('api:stations:reports:best-and-worst');

                    $group->get(
                        '/overview/most-played',
                        Controller\Api\Stations\Reports\Overview\MostPlayedAction::class
                    )->setName('api:stations:reports:most-played');

                    $group->get(
                        '/soundexchange',
                        Controller\Api\Stations\Reports\SoundExchangeAction::class
                    )->setName('api:stations:reports:soundexchange');
                }
            )->add(new Middleware\Permissions(StationPermissions::Reports, true));

            $group->get(
                '/streamers/schedule',
                Controller\Api\Stations\StreamersController::class . ':scheduleAction'
            )
                ->setName('api:stations:streamers:schedule')
                ->add(new Middleware\Permissions(StationPermissions::Streamers, true));

            $group->get(
                '/streamers/broadcasts',
                Controller\Api\Stations\Streamers\BroadcastsController::class . ':listAction'
            )
                ->setName('api:stations:streamers:broadcasts')
                ->add(new Middleware\Permissions(StationPermissions::Streamers, true));

            $group->group(
                '/streamer/{id}',
                function (RouteCollectorProxy $group) {
                    $group->get(
                        '/broadcasts',
                        Controller\Api\Stations\Streamers\BroadcastsController::class . ':listAction'
                    )
                        ->setName('api:stations:streamer:broadcasts');

                    $group->get(
                        '/broadcast/{broadcast_id}/download',
                        Controller\Api\Stations\Streamers\BroadcastsController::class . ':downloadAction'
                    )
                        ->setName('api:stations:streamer:broadcast:download');

                    $group->delete(
                        '/broadcast/{broadcast_id}',
                        Controller\Api\Stations\Streamers\BroadcastsController::class . ':deleteAction'
                    )
                        ->setName('api:stations:streamer:broadcast:delete');
                }
            )->add(new Middleware\Permissions(StationPermissions::Streamers, true));

            $group->get('/restart-status', Controller\Api\Stations\GetRestartStatusAction::class)
                ->setName('api:stations:restart-status')
                ->add(new Middleware\Permissions(StationPermissions::View, true));

            $group->get('/status', Controller\Api\Stations\ServicesController::class . ':statusAction')
                ->setName('api:stations:status')
                ->add(new Middleware\Permissions(StationPermissions::View, true));

            $group->post('/backend/{do}', Controller\Api\Stations\ServicesController::class . ':backendAction')
                ->setName('api:stations:backend')
                ->add(new Middleware\Permissions(StationPermissions::Broadcasting, true));

            $group->post(
                '/frontend/{do}',
                Controller\Api\Stations\ServicesController::class . ':frontendAction'
            )
                ->setName('api:stations:frontend')
                ->add(new Middleware\Permissions(StationPermissions::Broadcasting, true));

            $group->post('/reload', Controller\Api\Stations\ServicesController::class . ':reloadAction')
                ->setName('api:stations:reload')
                ->add(new Middleware\Permissions(StationPermissions::Broadcasting, true));

            $group->post('/restart', Controller\Api\Stations\ServicesController::class . ':restartAction')
                ->setName('api:stations:restart')
                ->add(new Middleware\Permissions(StationPermissions::Broadcasting, true));

            $group->group(
                '/fallback',
                function (RouteCollectorProxy $group) {
                    $group->get(
                        '',
                        Controller\Api\Stations\Fallback\GetFallbackAction::class
                    )->setName('api:stations:fallback');

                    $group->post(
                        '',
                        Controller\Api\Stations\Fallback\PostFallbackAction::class
                    );

                    $group->delete(
                        '',
                        Controller\Api\Stations\Fallback\DeleteFallbackAction::class
                    );
                }
            )->add(new Middleware\Permissions(StationPermissions::Broadcasting, true));

            $group->group(
                '/webhook/{id}',
                function (RouteCollectorProxy $group) {
                    $group->put(
                        '/toggle',
                        Controller\Api\Stations\Webhooks\ToggleAction::class
                    )->setName('api:stations:webhook:toggle');

                    $group->put(
                        '/test',
                        Controller\Api\Stations\Webhooks\TestAction::class
                    )->setName('api:stations:webhook:test');

                    $group->get(
                        '/test-log/{path}',
                        Controller\Api\Stations\Webhooks\TestLogAction::class
                    )->setName('api:stations:webhook:test-log');
                }
            )->add(new Middleware\Permissions(StationPermissions::WebHooks, true));
        }
    )->add(Middleware\RequireStation::class)
        ->add(Middleware\GetStation::class);
};
