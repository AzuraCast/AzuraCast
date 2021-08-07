<?php

use App\Acl;
use App\Controller;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Middleware;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app) {
    $app->group(
        '/api',
        function (RouteCollectorProxy $group) {
            $group->options(
                '/{routes:.+}',
                function (ServerRequest $request, Response $response) {
                    return $response
                        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                        ->withHeader(
                            'Access-Control-Allow-Headers',
                            'x-api-key, x-requested-with, Content-Type, Accept, Origin, Authorization'
                        )
                        ->withHeader('Access-Control-Allow-Origin', '*');
                }
            );

            $group->get('', Controller\Api\IndexController::class . ':indexAction')
                ->setName('api:index:index');

            $group->get('/openapi.yml', Controller\Api\OpenApiController::class)
                ->setName('api:openapi');

            $group->get('/status', Controller\Api\IndexController::class . ':statusAction')
                ->setName('api:index:status');

            $group->get('/time', Controller\Api\IndexController::class . ':timeAction')
                ->setName('api:index:time');

            $group->group(
                '/frontend',
                function (RouteCollectorProxy $group) {
                    $group->group(
                        '/account',
                        function (RouteCollectorProxy $group) {
                            $group->get('/me', Controller\Api\Frontend\Account\GetMeAction::class)
                                ->setName('api:frontend:account:me');

                            $group->put('/me', Controller\Api\Frontend\Account\PutMeAction::class);
                        }
                    );

                    $group->group(
                        '/dashboard',
                        function (RouteCollectorProxy $group) {
                            $group->get('/charts', Controller\Api\Frontend\Dashboard\ChartsAction::class)
                                ->setName('api:frontend:dashboard:charts');

                            $group->get('/notifications', Controller\Api\Frontend\Dashboard\NotificationsAction::class)
                                ->setName('api:frontend:dashboard:notifications');

                            $group->get('/stations', Controller\Api\Frontend\Dashboard\StationsAction::class)
                                ->setName('api:frontend:dashboard:stations');
                        }
                    );
                }
            )->add(Middleware\RequireLogin::class);

            $group->group(
                '/internal',
                function (RouteCollectorProxy $group) {
                    $group->group(
                        '/{station_id}',
                        function (RouteCollectorProxy $group) {
                            // Liquidsoap internal authentication functions
                            $group->map(
                                ['GET', 'POST'],
                                '/auth',
                                Controller\Api\InternalController::class . ':authAction'
                            )->setName('api:internal:auth');

                            $group->map(
                                ['GET', 'POST'],
                                '/nextsong',
                                Controller\Api\InternalController::class . ':nextsongAction'
                            )->setName('api:internal:nextsong');

                            $group->map(
                                ['GET', 'POST'],
                                '/djon',
                                Controller\Api\InternalController::class . ':djonAction'
                            )->setName('api:internal:djon');

                            $group->map(
                                ['GET', 'POST'],
                                '/djoff',
                                Controller\Api\InternalController::class . ':djoffAction'
                            )->setName('api:internal:djoff');

                            $group->map(
                                ['GET', 'POST'],
                                '/feedback',
                                Controller\Api\InternalController::class . ':feedbackAction'
                            )->setName('api:internal:feedback');
                        }
                    )->add(Middleware\GetStation::class);

                    $group->get('/relays', Controller\Api\Admin\RelaysController::class)
                        ->setName('api:internal:relays')
                        ->add(Middleware\RequireLogin::class);

                    $group->post('/relays', Controller\Api\Admin\RelaysController::class . ':updateAction')
                        ->add(Middleware\RequireLogin::class);
                }
            );

            $group->get('/nowplaying[/{station_id}]', Controller\Api\NowplayingController::class)
                ->setName('api:nowplaying:index');

            $group->get('/stations', Controller\Api\Stations\IndexController::class . ':listAction')
                ->setName('api:stations:list')
                ->add(new Middleware\RateLimit('api'));

            $group->group(
                '/admin',
                function (RouteCollectorProxy $group) {
                    $group->get('/auditlog', Controller\Api\Admin\AuditLogController::class)
                        ->setName('api:admin:auditlog')
                        ->add(new Middleware\Permissions(Acl::GLOBAL_LOGS));

                    $group->get('/permissions', Controller\Api\Admin\PermissionsController::class)
                        ->add(new Middleware\Permissions(Acl::GLOBAL_ALL));

                    $group->map(
                        ['GET', 'POST'],
                        '/relays',
                        function (ServerRequest $request, Response $response) {
                            return $response->withRedirect(
                                (string)$request->getRouter()->fromHere('api:internal:relays')
                            );
                        }
                    );

                    $group->group(
                        '',
                        function (RouteCollectorProxy $group) {
                            $group->get('/settings', Controller\Api\Admin\SettingsController::class . ':listAction')
                                ->setName('api:admin:settings');

                            $group->put('/settings', Controller\Api\Admin\SettingsController::class . ':updateAction');

                            $group->get(
                                '/custom_assets/{type}',
                                Controller\Api\Admin\CustomAssets\GetCustomAssetAction::class
                            )->setName('api:admin:custom_assets');

                            $group->post(
                                '/custom_assets/{type}',
                                Controller\Api\Admin\CustomAssets\PostCustomAssetAction::class
                            );
                            $group->delete(
                                '/custom_assets/{type}',
                                Controller\Api\Admin\CustomAssets\DeleteCustomAssetAction::class
                            );
                        }
                    )->add(new Middleware\Permissions(Acl::GLOBAL_SETTINGS));

                    $admin_api_endpoints = [
                        [
                            'custom_field',
                            'custom_fields',
                            Controller\Api\Admin\CustomFieldsController::class,
                            Acl::GLOBAL_CUSTOM_FIELDS,
                        ],
                        ['role', 'roles', Controller\Api\Admin\RolesController::class, Acl::GLOBAL_ALL],
                        ['station', 'stations', Controller\Api\Admin\StationsController::class, Acl::GLOBAL_STATIONS],
                        ['user', 'users', Controller\Api\Admin\UsersController::class, Acl::GLOBAL_ALL],
                        [
                            'storage_location',
                            'storage_locations',
                            Controller\Api\Admin\StorageLocationsController::class,
                            Acl::GLOBAL_STORAGE_LOCATIONS,
                        ],
                    ];

                    foreach ($admin_api_endpoints as [$singular, $plural, $class, $permission]) {
                        $group->group(
                            '',
                            function (RouteCollectorProxy $group) use ($singular, $plural, $class) {
                                $group->get('/' . $plural, $class . ':listAction')
                                    ->setName('api:admin:' . $plural);
                                $group->post('/' . $plural, $class . ':createAction');

                                $group->get('/' . $singular . '/{id}', $class . ':getAction')
                                    ->setName('api:admin:' . $singular);
                                $group->put('/' . $singular . '/{id}', $class . ':editAction');
                                $group->delete('/' . $singular . '/{id}', $class . ':deleteAction');
                            }
                        )->add(new Middleware\Permissions($permission));
                    }
                }
            );

            $group->group(
                '/station/{station_id}',
                function (RouteCollectorProxy $group) {
                    $group->get('', Controller\Api\Stations\IndexController::class . ':indexAction')
                        ->setName('api:stations:index')
                        ->add(new Middleware\RateLimit('api', 5, 2));

                    $group->get('/nowplaying', Controller\Api\NowplayingController::class . ':indexAction');

                    $group->map(
                        ['GET', 'POST'],
                        '/nowplaying/update',
                        Controller\Api\Stations\UpdateMetadataController::class
                    )
                        ->add(new Middleware\Permissions(Acl::STATION_BROADCASTING, true));

                    $group->get('/profile', Controller\Api\Stations\ProfileController::class)
                        ->setName('api:stations:profile')
                        ->add(new Middleware\Permissions(Acl::STATION_VIEW, true));

                    $group->get('/schedule', Controller\Api\Stations\ScheduleController::class)
                        ->setName('api:stations:schedule');

                    $group->get('/history', Controller\Api\Stations\HistoryController::class)
                        ->setName('api:stations:history')
                        ->add(new Middleware\Permissions(Acl::STATION_REPORTS, true));

                    $group->group(
                        '/queue',
                        function (RouteCollectorProxy $group) {
                            $group->get('', Controller\Api\Stations\QueueController::class . ':listAction')
                                ->setName('api:stations:queue');

                            $group->get('/{id}', Controller\Api\Stations\QueueController::class . ':getAction')
                                ->setName('api:stations:queue:record');

                            $group->delete('/{id}', Controller\Api\Stations\QueueController::class . ':deleteAction');
                        }
                    )->add(new Middleware\Permissions(Acl::STATION_BROADCASTING, true));

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
                        ->add(new Middleware\Permissions(Acl::STATION_REPORTS, true));

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
                        ->add(new Middleware\Permissions(Acl::STATION_MEDIA, true));

                    $group->delete('/art/{media_id:[a-zA-Z0-9]+}', Controller\Api\Stations\Art\DeleteArtAction::class)
                        ->add(new Middleware\Permissions(Acl::STATION_MEDIA, true));

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
                    )->add(new Middleware\Permissions(Acl::STATION_PODCASTS, true));

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
                    )->add(new Middleware\Permissions(Acl::STATION_PODCASTS, true));

                    $station_api_endpoints = [
                        ['file', 'files', Controller\Api\Stations\FilesController::class, Acl::STATION_MEDIA],
                        ['mount', 'mounts', Controller\Api\Stations\MountsController::class, Acl::STATION_MOUNTS],
                        [
                            'playlist',
                            'playlists',
                            Controller\Api\Stations\PlaylistsController::class,
                            Acl::STATION_MEDIA,
                        ],
                        ['remote', 'remotes', Controller\Api\Stations\RemotesController::class, Acl::STATION_REMOTES],
                        [
                            'streamer',
                            'streamers',
                            Controller\Api\Stations\StreamersController::class,
                            Acl::STATION_STREAMERS,
                        ],
                        [
                            'webhook',
                            'webhooks',
                            Controller\Api\Stations\WebhooksController::class,
                            Acl::STATION_WEB_HOOKS,
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

                            $group->map(
                                ['GET', 'POST'],
                                '/upload',
                                Controller\Api\Stations\Files\FlowUploadAction::class
                            )->setName('api:stations:files:upload');
                        }
                    )
                        ->add(Middleware\Module\StationFiles::class)
                        ->add(new Middleware\Permissions(Acl::STATION_MEDIA, true));

                    $group->post(
                        '/mounts/intro',
                        Controller\Api\Stations\Mounts\Intro\PostIntroAction::class
                    )->setName('api:stations:mounts:new-intro')
                        ->add(new Middleware\Permissions(Acl::STATION_MOUNTS, true));

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
                    )->add(new Middleware\Permissions(Acl::STATION_MOUNTS, true));

                    $group->get(
                        '/playlists/schedule',
                        Controller\Api\Stations\PlaylistsController::class . ':scheduleAction'
                    )
                        ->setName('api:stations:playlists:schedule')
                        ->add(new Middleware\Permissions(Acl::STATION_MEDIA, true));

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
                    )->add(new Middleware\Permissions(Acl::STATION_MEDIA, true));

                    $group->group(
                        '/reports',
                        function (RouteCollectorProxy $group) {
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
                        }
                    )->add(new Middleware\Permissions(Acl::STATION_REPORTS, true));

                    $group->get(
                        '/streamers/schedule',
                        Controller\Api\Stations\StreamersController::class . ':scheduleAction'
                    )
                        ->setName('api:stations:streamers:schedule')
                        ->add(new Middleware\Permissions(Acl::STATION_STREAMERS, true));

                    $group->get(
                        '/streamers/broadcasts',
                        Controller\Api\Stations\Streamers\BroadcastsController::class . ':listAction'
                    )
                        ->setName('api:stations:streamers:broadcasts')
                        ->add(new Middleware\Permissions(Acl::STATION_STREAMERS, true));

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
                    )->add(new Middleware\Permissions(Acl::STATION_STREAMERS, true));

                    $group->get('/status', Controller\Api\Stations\ServicesController::class . ':statusAction')
                        ->setName('api:stations:status')
                        ->add(new Middleware\Permissions(Acl::STATION_VIEW, true));

                    $group->post('/backend/{do}', Controller\Api\Stations\ServicesController::class . ':backendAction')
                        ->setName('api:stations:backend')
                        ->add(new Middleware\Permissions(Acl::STATION_BROADCASTING, true));

                    $group->post(
                        '/frontend/{do}',
                        Controller\Api\Stations\ServicesController::class . ':frontendAction'
                    )
                        ->setName('api:stations:frontend')
                        ->add(new Middleware\Permissions(Acl::STATION_BROADCASTING, true));

                    $group->post('/restart', Controller\Api\Stations\ServicesController::class . ':restartAction')
                        ->setName('api:stations:restart')
                        ->add(new Middleware\Permissions(Acl::STATION_BROADCASTING, true));
                }
            )->add(Middleware\RequireStation::class)
                ->add(Middleware\GetStation::class);
        }
    )->add(Middleware\Module\Api::class);
};
