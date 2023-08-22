<?php

declare(strict_types=1);

use App\Controller;
use App\Enums\StationFeatures;
use App\Enums\StationPermissions;
use App\Middleware;
use Slim\Routing\RouteCollectorProxy;

return static function (RouteCollectorProxy $group) {
    $group->group(
        '/station/{station_id}',
        function (RouteCollectorProxy $group) {
            /*
             * Anonymous Functions
             */
            $group->get('', Controller\Api\Stations\IndexController::class . ':indexAction')
                ->setName('api:stations:index')
                ->add(new Middleware\RateLimit('api', 5, 2));

            $group->get('/nowplaying', Controller\Api\NowPlayingController::class . ':getAction');

            $group->get('/schedule', Controller\Api\Stations\ScheduleAction::class)
                ->setName('api:stations:schedule');

            // Song Requests
            $group->get('/requests', Controller\Api\Stations\Requests\ListAction::class)
                ->add(new Middleware\StationSupportsFeature(StationFeatures::Requests))
                ->setName('api:requests:list');

            $group->map(
                ['GET', 'POST'],
                '/request/{media_id}',
                Controller\Api\Stations\Requests\SubmitAction::class
            )
                ->setName('api:requests:submit')
                ->add(new Middleware\StationSupportsFeature(StationFeatures::Requests))
                ->add(new Middleware\RateLimit('api', 5, 2));

            // On-Demand Streaming
            $group->get('/ondemand', Controller\Api\Stations\OnDemand\ListAction::class)
                ->setName('api:stations:ondemand:list');

            $group->get('/ondemand/download/{media_id}', Controller\Api\Stations\OnDemand\DownloadAction::class)
                ->setName('api:stations:ondemand:download')
                ->add(new Middleware\RateLimit('ondemand', 1, 2));

            // Podcast Public Pages
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

            // Media Art
            $group->get('/art/{media_id:[a-zA-Z0-9\-]+}.jpg', Controller\Api\Stations\Art\GetArtAction::class)
                ->setName('api:stations:media:art');

            $group->get('/art/{media_id:[a-zA-Z0-9\-]+}', Controller\Api\Stations\Art\GetArtAction::class)
                ->setName('api:stations:media:art-internal');

            // Streamer Art
            $group->get(
                '/streamer/{id}/art',
                Controller\Api\Stations\Streamers\Art\GetArtAction::class
            )->setName('api:stations:streamer:art');

            /*
             * Authenticated Functions
             */
            $group->group(
                '',
                function (RouteCollectorProxy $group) {
                    $group->map(
                        ['GET', 'POST'],
                        '/nowplaying/update',
                        Controller\Api\Stations\UpdateMetadataAction::class
                    )->add(new Middleware\Permissions(StationPermissions::Broadcasting, true));

                    $group->get('/profile', Controller\Api\Stations\ProfileAction::class)
                        ->setName('api:stations:profile')
                        ->add(new Middleware\Permissions(StationPermissions::View, true));

                    $group->group(
                        '',
                        function (RouteCollectorProxy $group) {
                            $group->get(
                                '/profile/edit',
                                Controller\Api\Stations\ProfileEditController::class . ':getProfileAction'
                            )->setName('api:stations:profile:edit');

                            $group->put(
                                '/profile/edit',
                                Controller\Api\Stations\ProfileEditController::class . ':putProfileAction'
                            );

                            $group->get(
                                '/custom_assets/{type}',
                                Controller\Api\Stations\CustomAssets\GetCustomAssetAction::class
                            )->setName('api:stations:custom_assets');

                            $group->post(
                                '/custom_assets/{type}',
                                Controller\Api\Stations\CustomAssets\PostCustomAssetAction::class
                            );
                            $group->delete(
                                '/custom_assets/{type}',
                                Controller\Api\Stations\CustomAssets\DeleteCustomAssetAction::class
                            );
                        }
                    )->add(new Middleware\Permissions(StationPermissions::Profile, true));

                    // Upcoming Song Queue
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

                    // Podcast Private Pages
                    $group->group(
                        '',
                        function (RouteCollectorProxy $group) {
                            $group->get('/podcasts', Controller\Api\Stations\PodcastsController::class . ':listAction')
                                ->setName('api:stations:podcasts');

                            $group->post(
                                '/podcasts',
                                Controller\Api\Stations\PodcastsController::class . ':createAction'
                            );

                            $group->post('/podcasts/art', Controller\Api\Stations\Podcasts\Art\PostArtAction::class)
                                ->setName('api:stations:podcasts:new-art');

                            $group->group(
                                '/podcast/{podcast_id}',
                                function (RouteCollectorProxy $group) {
                                    $group->put('', Controller\Api\Stations\PodcastsController::class . ':editAction');

                                    $group->delete(
                                        '',
                                        Controller\Api\Stations\PodcastsController::class . ':deleteAction'
                                    );

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
                                                Controller\Api\Stations\PodcastEpisodesController::class
                                                . ':deleteAction'
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
                            );
                        }
                    )->add(new Middleware\Permissions(StationPermissions::Podcasts, true));

                    // Files/Media
                    $group->get('/quota[/{type}]', Controller\Api\Stations\GetQuotaAction::class)
                        ->setName('api:stations:quota')
                        ->add(new Middleware\Permissions(StationPermissions::View, true));

                    $group->get(
                        '/waveform/{media_id:[a-zA-Z0-9\-]+}.json',
                        Controller\Api\Stations\Waveform\GetWaveformAction::class
                    )->setName('api:stations:media:waveform');

                    $group->post('/art/{media_id:[a-zA-Z0-9]+}', Controller\Api\Stations\Art\PostArtAction::class)
                        ->add(new Middleware\Permissions(StationPermissions::Media, true));

                    $group->delete('/art/{media_id:[a-zA-Z0-9]+}', Controller\Api\Stations\Art\DeleteArtAction::class)
                        ->add(new Middleware\Permissions(StationPermissions::Media, true));

                    $group->group(
                        '',
                        function (RouteCollectorProxy $group) {
                            $group->group(
                                '/files',
                                function (RouteCollectorProxy $group) {
                                    $group->get(
                                        '',
                                        Controller\Api\Stations\FilesController::class . ':listAction'
                                    )->setName('api:stations:files');

                                    $group->post(
                                        '',
                                        Controller\Api\Stations\FilesController::class . ':createAction'
                                    );

                                    $group->get('/list', Controller\Api\Stations\Files\ListAction::class)
                                        ->setName('api:stations:files:list');

                                    $group->get(
                                        '/directories',
                                        Controller\Api\Stations\Files\ListDirectoriesAction::class
                                    )
                                        ->setName('api:stations:files:directories');

                                    $group->put('/rename', Controller\Api\Stations\Files\RenameAction::class)
                                        ->setName('api:stations:files:rename');

                                    $group->put('/batch', Controller\Api\Stations\Files\BatchAction::class)
                                        ->setName('api:stations:files:batch');

                                    $group->post('/mkdir', Controller\Api\Stations\Files\MakeDirectoryAction::class)
                                        ->setName('api:stations:files:mkdir');

                                    $group->get('/bulk', Controller\Api\Stations\BulkMedia\DownloadAction::class)
                                        ->setName('api:stations:files:bulk');

                                    $group->post('/bulk', Controller\Api\Stations\BulkMedia\UploadAction::class);

                                    $group->get('/download', Controller\Api\Stations\Files\DownloadAction::class)
                                        ->setName('api:stations:files:download');

                                    $group->map(
                                        ['GET', 'POST'],
                                        '/upload',
                                        Controller\Api\Stations\Files\FlowUploadAction::class
                                    )->setName('api:stations:files:upload');
                                }
                            );

                            $group->group(
                                '/file/{id}',
                                function (RouteCollectorProxy $group) {
                                    $group->get(
                                        '',
                                        Controller\Api\Stations\FilesController::class . ':getAction'
                                    )->setName('api:stations:file');

                                    $group->put(
                                        '',
                                        Controller\Api\Stations\FilesController::class . ':editAction'
                                    );

                                    $group->delete(
                                        '',
                                        Controller\Api\Stations\FilesController::class . ':deleteAction'
                                    );

                                    $group->get('/play', Controller\Api\Stations\Files\PlayAction::class)
                                        ->setName('api:stations:files:play');
                                }
                            );
                        }
                    )->add(new Middleware\StationSupportsFeature(StationFeatures::Media))
                        ->add(new Middleware\Permissions(StationPermissions::Media, true));

                    // SFTP Users
                    $group->group(
                        '',
                        function (RouteCollectorProxy $group) {
                            $group->get(
                                '/sftp-users',
                                Controller\Api\Stations\SftpUsersController::class . ':listAction'
                            )->setName('api:stations:sftp-users');

                            $group->post(
                                '/sftp-users',
                                Controller\Api\Stations\SftpUsersController::class . ':createAction'
                            );

                            $group->get(
                                '/sftp-user/{id}',
                                Controller\Api\Stations\SftpUsersController::class . ':getAction'
                            )->setName('api:stations:sftp-user');

                            $group->put(
                                '/sftp-user/{id}',
                                Controller\Api\Stations\SftpUsersController::class . ':editAction'
                            );
                            $group->delete(
                                '/sftp-user/{id}',
                                Controller\Api\Stations\SftpUsersController::class . ':deleteAction'
                            );
                        }
                    )->add(new Middleware\StationSupportsFeature(StationFeatures::Sftp))
                        ->add(new Middleware\Permissions(StationPermissions::Media, true));

                    // Mount Points
                    $group->group(
                        '',
                        function (RouteCollectorProxy $group) {
                            $group->group(
                                '/mounts',
                                function (RouteCollectorProxy $group) {
                                    $group->get(
                                        '',
                                        Controller\Api\Stations\MountsController::class . ':listAction'
                                    )->setName('api:stations:mounts');

                                    $group->post(
                                        '',
                                        Controller\Api\Stations\MountsController::class . ':createAction'
                                    );

                                    $group->post(
                                        '/intro',
                                        Controller\Api\Stations\Mounts\Intro\PostIntroAction::class
                                    )->setName('api:stations:mounts:new-intro');
                                }
                            );

                            $group->group(
                                '/mount/{id}',
                                function (RouteCollectorProxy $group) {
                                    $group->get(
                                        '',
                                        Controller\Api\Stations\MountsController::class . ':getAction'
                                    )->setName('api:stations:mount');

                                    $group->put(
                                        '',
                                        Controller\Api\Stations\MountsController::class . ':editAction'
                                    );

                                    $group->delete(
                                        '',
                                        Controller\Api\Stations\MountsController::class . ':deleteAction'
                                    );

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
                            );
                        }
                    )->add(new Middleware\StationSupportsFeature(StationFeatures::MountPoints))
                        ->add(new Middleware\Permissions(StationPermissions::MountPoints, true));

                    // Remote Relays
                    $group->group(
                        '',
                        function (RouteCollectorProxy $group) {
                            $group->get(
                                '/remotes',
                                Controller\Api\Stations\RemotesController::class . ':listAction'
                            )->setName('api:stations:remotes');

                            $group->post(
                                '/remotes',
                                Controller\Api\Stations\RemotesController::class . ':createAction'
                            );

                            $group->get(
                                '/remote/{id}',
                                Controller\Api\Stations\RemotesController::class . ':getAction'
                            )->setName('api:stations:remote');

                            $group->put(
                                '/remote/{id}',
                                Controller\Api\Stations\RemotesController::class . ':editAction'
                            );

                            $group->delete(
                                '/remote/{id}',
                                Controller\Api\Stations\RemotesController::class . ':deleteAction'
                            );
                        }
                    )->add(new Middleware\StationSupportsFeature(StationFeatures::RemoteRelays))
                        ->add(new Middleware\Permissions(StationPermissions::RemoteRelays, true));

                    // HLS Streams
                    $group->group(
                        '',
                        function (RouteCollectorProxy $group) {
                            $group->get(
                                '/hls_streams',
                                Controller\Api\Stations\HlsStreamsController::class . ':listAction'
                            )->setName('api:stations:hls_streams');

                            $group->post(
                                '/hls_streams',
                                Controller\Api\Stations\HlsStreamsController::class . ':createAction'
                            );

                            $group->get(
                                '/hls_stream/{id}',
                                Controller\Api\Stations\HlsStreamsController::class . ':getAction'
                            )->setName('api:stations:hls_stream');

                            $group->put(
                                '/hls_stream/{id}',
                                Controller\Api\Stations\HlsStreamsController::class . ':editAction'
                            );

                            $group->delete(
                                '/hls_stream/{id}',
                                Controller\Api\Stations\HlsStreamsController::class . ':deleteAction'
                            );
                        }
                    )->add(new Middleware\StationSupportsFeature(StationFeatures::HlsStreams))
                        ->add(new Middleware\Permissions(StationPermissions::MountPoints, true));

                    // Playlist
                    $group->group(
                        '',
                        function (RouteCollectorProxy $group) {
                            $group->get(
                                '/playlists',
                                Controller\Api\Stations\PlaylistsController::class . ':listAction'
                            )->setName('api:stations:playlists');

                            $group->post(
                                '/playlists',
                                Controller\Api\Stations\PlaylistsController::class . ':createAction'
                            );

                            $group->get(
                                '/playlists/schedule',
                                Controller\Api\Stations\PlaylistsController::class . ':scheduleAction'
                            )->setName('api:stations:playlists:schedule');

                            $group->group(
                                '/playlist/{id}',
                                function (RouteCollectorProxy $group) {
                                    $group->get(
                                        '',
                                        Controller\Api\Stations\PlaylistsController::class . ':getAction'
                                    )->setName('api:stations:playlist');

                                    $group->put(
                                        '',
                                        Controller\Api\Stations\PlaylistsController::class . ':editAction'
                                    );

                                    $group->delete(
                                        '',
                                        Controller\Api\Stations\PlaylistsController::class . ':deleteAction'
                                    );

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

                                    $group->get(
                                        '/apply-to',
                                        Controller\Api\Stations\Playlists\GetApplyToAction::class
                                    )->setName('api:stations:playlist:applyto');

                                    $group->put(
                                        '/apply-to',
                                        Controller\Api\Stations\Playlists\PutApplyToAction::class
                                    );

                                    $group->delete(
                                        '/empty',
                                        Controller\Api\Stations\Playlists\EmptyAction::class
                                    )->setName('api:stations:playlist:empty');
                                }
                            );
                        }
                    )->add(new Middleware\StationSupportsFeature(StationFeatures::Media))
                        ->add(new Middleware\Permissions(StationPermissions::Media, true));

                    // Reports
                    $group->get('/history', Controller\Api\Stations\HistoryAction::class)
                        ->setName('api:stations:history')
                        ->add(new Middleware\Permissions(StationPermissions::Reports, true));

                    $group->get('/listeners', Controller\Api\Stations\ListenersAction::class)
                        ->setName('api:listeners:index')
                        ->add(new Middleware\Permissions(StationPermissions::Reports, true));

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
                                '/overview/charts',
                                Controller\Api\Stations\Reports\Overview\ChartsAction::class
                            )->setName('api:stations:reports:overview-charts');

                            $group->get(
                                '/overview/best-and-worst',
                                Controller\Api\Stations\Reports\Overview\BestAndWorstAction::class
                            )->setName('api:stations:reports:best-and-worst');

                            $group->get(
                                '/overview/by-browser',
                                Controller\Api\Stations\Reports\Overview\ByBrowser::class
                            )->setName('api:stations:reports:by-browser');

                            $group->get(
                                '/overview/by-country',
                                Controller\Api\Stations\Reports\Overview\ByCountry::class
                            )->setName('api:stations:reports:by-country');

                            $group->get(
                                '/overview/by-stream',
                                Controller\Api\Stations\Reports\Overview\ByStream::class
                            )->setName('api:stations:reports:by-stream');

                            $group->get(
                                '/overview/by-client',
                                Controller\Api\Stations\Reports\Overview\ByClient::class
                            )->setName('api:stations:reports:by-client');

                            $group->get(
                                '/overview/by-listening-time',
                                Controller\Api\Stations\Reports\Overview\ByListeningTime::class
                            )->setName('api:stations:reports:by-listening-time');

                            $group->get(
                                '/soundexchange',
                                Controller\Api\Stations\Reports\SoundExchangeAction::class
                            )->setName('api:stations:reports:soundexchange');
                        }
                    )->add(new Middleware\Permissions(StationPermissions::Reports, true));

                    // Streamers
                    $group->group(
                        '',
                        function (RouteCollectorProxy $group) {
                            $group->group(
                                '/streamers',
                                function (RouteCollectorProxy $group) {
                                    $group->get(
                                        '',
                                        Controller\Api\Stations\StreamersController::class . ':listAction'
                                    )->setName('api:stations:streamers');

                                    $group->post(
                                        '',
                                        Controller\Api\Stations\StreamersController::class . ':createAction'
                                    );

                                    $group->get(
                                        '/schedule',
                                        Controller\Api\Stations\StreamersController::class . ':scheduleAction'
                                    )->setName('api:stations:streamers:schedule');

                                    $group->get(
                                        '/broadcasts',
                                        Controller\Api\Stations\Streamers\BroadcastsController::class . ':listAction'
                                    )->setName('api:stations:streamers:broadcasts');

                                    $group->post(
                                        '/art',
                                        Controller\Api\Stations\Streamers\Art\PostArtAction::class
                                    )->setName('api:stations:streamers:new-art');
                                }
                            );

                            $group->group(
                                '/streamer/{id}',
                                function (RouteCollectorProxy $group) {
                                    $group->get(
                                        '',
                                        Controller\Api\Stations\StreamersController::class . ':getAction'
                                    )->setName('api:stations:streamer');

                                    $group->put(
                                        '',
                                        Controller\Api\Stations\StreamersController::class . ':editAction'
                                    );

                                    $group->delete(
                                        '',
                                        Controller\Api\Stations\StreamersController::class . ':deleteAction'
                                    );

                                    $group->get(
                                        '/broadcasts',
                                        Controller\Api\Stations\Streamers\BroadcastsController::class . ':listAction'
                                    )->setName('api:stations:streamer:broadcasts');

                                    $group->get(
                                        '/broadcast/{broadcast_id}/download',
                                        Controller\Api\Stations\Streamers\BroadcastsController::class
                                        . ':downloadAction'
                                    )->setName('api:stations:streamer:broadcast:download');

                                    $group->delete(
                                        '/broadcast/{broadcast_id}',
                                        Controller\Api\Stations\Streamers\BroadcastsController::class . ':deleteAction'
                                    )->setName('api:stations:streamer:broadcast:delete');

                                    $group->post(
                                        '/art',
                                        Controller\Api\Stations\Streamers\Art\PostArtAction::class
                                    )->setName('api:stations:streamer:art-internal');

                                    $group->delete(
                                        '/art',
                                        Controller\Api\Stations\Streamers\Art\DeleteArtAction::class
                                    );
                                }
                            );
                        }
                    )->add(new Middleware\StationSupportsFeature(StationFeatures::Streamers))
                        ->add(new Middleware\Permissions(StationPermissions::Streamers, true));

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
                    )->setName('api:stations:frontend')
                        ->add(new Middleware\Permissions(StationPermissions::Broadcasting, true));

                    $group->post('/reload', Controller\Api\Stations\ServicesController::class . ':reloadAction')
                        ->setName('api:stations:reload')
                        ->add(new Middleware\Permissions(StationPermissions::Broadcasting, true));

                    $group->post('/restart', Controller\Api\Stations\ServicesController::class . ':restartAction')
                        ->setName('api:stations:restart')
                        ->add(new Middleware\Permissions(StationPermissions::Broadcasting, true));

                    // Custom Fallback File
                    $group->group(
                        '/fallback',
                        function (RouteCollectorProxy $group) {
                            $group->get(
                                '[/{do}]',
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

                    // Webhook Extras
                    $group->group(
                        '',
                        function (RouteCollectorProxy $group) {
                            $group->get(
                                '/webhooks',
                                Controller\Api\Stations\WebhooksController::class . ':listAction'
                            )->setName('api:stations:webhooks');

                            $group->post(
                                '/webhooks',
                                Controller\Api\Stations\WebhooksController::class . ':createAction'
                            );

                            $group->group(
                                '/webhook/{id}',
                                function (RouteCollectorProxy $group) {
                                    $group->get(
                                        '',
                                        Controller\Api\Stations\WebhooksController::class . ':getAction'
                                    )->setName('api:stations:webhook');

                                    $group->put(
                                        '',
                                        Controller\Api\Stations\WebhooksController::class . ':editAction'
                                    );

                                    $group->delete(
                                        '',
                                        Controller\Api\Stations\WebhooksController::class . ':deleteAction'
                                    );

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
                            );
                        }
                    )->add(new Middleware\StationSupportsFeature(StationFeatures::Webhooks))
                        ->add(new Middleware\Permissions(StationPermissions::WebHooks, true));

                    // Custom Liquidsoap Configuration
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

                    // StereoTool Configuration
                    $group->group(
                        '/stereo_tool_config',
                        function (RouteCollectorProxy $group) {
                            $group->get(
                                '[/{do}]',
                                Controller\Api\Stations\StereoTool\GetStereoToolConfigurationAction::class
                            )->setName('api:stations:stereo_tool_config');

                            $group->post(
                                '',
                                Controller\Api\Stations\StereoTool\PostStereoToolConfigurationAction::class
                            );

                            $group->delete(
                                '',
                                Controller\Api\Stations\StereoTool\DeleteStereoToolConfigurationAction::class
                            );
                        }
                    )->add(new Middleware\Permissions(StationPermissions::Broadcasting, true));

                    // Logs
                    $group->group(
                        '',
                        function (RouteCollectorProxy $group) {
                            $group->get('/logs', Controller\Api\Stations\LogsAction::class)
                                ->setName('api:stations:logs');

                            $group->get('/log/{log}', Controller\Api\Stations\LogsAction::class)
                                ->setName('api:stations:log');
                        }
                    )->add(new Middleware\Permissions(StationPermissions::Logs, true));

                    // Vue Properties
                    call_user_func(include(__DIR__ . '/api_station_vue.php'), $group);
                }
            )->add(Middleware\RequireLogin::class);
        }
    )->add(Middleware\RequireStation::class)
        ->add(Middleware\GetStation::class);
};
