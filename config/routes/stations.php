<?php

use App\Controller;
use App\Enums\StationFeatures;
use App\Enums\StationPermissions;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Middleware;
use Slim\Routing\RouteCollectorProxy;

return static function (RouteCollectorProxy $app) {
    $app->group(
        '/station/{station_id}',
        function (RouteCollectorProxy $group) {
            $group->get(
                '',
                function (ServerRequest $request, Response $response, ...$params) {
                    return $response->withRedirect(
                        $request->getRouter()->fromHere('stations:profile:index')
                    );
                }
            )->setName('stations:index:index');

            $group->get('/bulk-media', Controller\Stations\BulkMediaAction::class)
                ->setName('stations:bulk-media')
                ->add(new Middleware\Permissions(StationPermissions::Media, true));

            $group->get('/fallback', Controller\Stations\FallbackAction::class)
                ->setName('stations:fallback')
                ->add(new Middleware\Permissions(StationPermissions::Broadcasting, true));

            $group->get('/files', Controller\Stations\FilesAction::class)
                ->setName('stations:files:index')
                ->add(new Middleware\StationSupportsFeature(StationFeatures::Media))
                ->add(new Middleware\Permissions(StationPermissions::Media, true));

            $group->get('/hls_streams', Controller\Stations\HlsStreamsAction::class)
                ->setName('stations:hls_streams:index')
                ->add(new Middleware\StationSupportsFeature(StationFeatures::HlsStreams))
                ->add(new Middleware\Permissions(StationPermissions::MountPoints, true));

            $group->get('/ls_config', Controller\Stations\EditLiquidsoapConfigAction::class)
                ->setName('stations:util:ls_config')
                ->add(new Middleware\StationSupportsFeature(StationFeatures::CustomLiquidsoapConfig))
                ->add(new Middleware\Permissions(StationPermissions::Broadcasting, true));

            $group->get('/stereo_tool_config', Controller\Stations\UploadStereoToolConfigAction::class)
                ->setName('stations:stereo_tool_config')
                ->add(new Middleware\Permissions(StationPermissions::Broadcasting, true));

            $group->get('/logs', Controller\Stations\LogsAction::class)
                ->setName('stations:logs')
                ->add(new Middleware\Permissions(StationPermissions::Logs, true));

            $group->get('/playlists', Controller\Stations\PlaylistsAction::class)
                ->setName('stations:playlists:index')
                ->add(new Middleware\StationSupportsFeature(StationFeatures::Media))
                ->add(new Middleware\Permissions(StationPermissions::Media, true));

            $group->get('/podcasts', Controller\Stations\PodcastsAction::class)
                ->setName('stations:podcasts:index')
                ->add(new Middleware\StationSupportsFeature(StationFeatures::Podcasts))
                ->add(new Middleware\Permissions(StationPermissions::Podcasts, true));

            $group->get('/mounts', Controller\Stations\MountsAction::class)
                ->setName('stations:mounts:index')
                ->add(new Middleware\StationSupportsFeature(StationFeatures::MountPoints))
                ->add(new Middleware\Permissions(StationPermissions::MountPoints, true));

            $group->get('/profile', Controller\Stations\ProfileController::class)
                ->setName('stations:profile:index');

            $group->get(
                '/profile/toggle/{feature}/{csrf}',
                Controller\Stations\ProfileController::class . ':toggleAction'
            )
                ->setName('stations:profile:toggle')
                ->add(new Middleware\Permissions(StationPermissions::Profile, true));

            $group->get('/profile/edit', Controller\Stations\ProfileController::class . ':editAction')
                ->setName('stations:profile:edit')
                ->add(new Middleware\Permissions(StationPermissions::Profile, true));

            $group->get('/queue', Controller\Stations\QueueAction::class)
                ->setName('stations:queue:index')
                ->add(new Middleware\StationSupportsFeature(StationFeatures::Media))
                ->add(new Middleware\Permissions(StationPermissions::Broadcasting, true));

            $group->get('/remotes', Controller\Stations\RemotesAction::class)
                ->setName('stations:remotes:index')
                ->add(new Middleware\StationSupportsFeature(StationFeatures::RemoteRelays))
                ->add(new Middleware\Permissions(StationPermissions::RemoteRelays, true));

            $group->group(
                '/reports',
                function (RouteCollectorProxy $group) {
                    $group->get('/overview', Controller\Stations\Reports\OverviewAction::class)
                        ->setName('stations:reports:overview');

                    $group->get('/timeline', Controller\Stations\Reports\TimelineAction::class)
                        ->setName('stations:reports:timeline');

                    $group->get('/listeners', Controller\Stations\Reports\ListenersAction::class)
                        ->setName('stations:reports:listeners');

                    $group->map(
                        ['GET', 'POST'],
                        '/soundexchange',
                        Controller\Stations\Reports\SoundExchangeAction::class
                    )
                        ->setName('stations:reports:soundexchange');

                    $group->get('/requests', Controller\Stations\Reports\RequestsAction::class)
                        ->setName('stations:reports:requests');
                }
            )->add(new Middleware\Permissions(StationPermissions::Reports, true));

            $group->get('/sftp_users', Controller\Stations\SftpUsersAction::class)
                ->setName('stations:sftp_users:index')
                ->add(new Middleware\StationSupportsFeature(StationFeatures::Sftp))
                ->add(new Middleware\Permissions(StationPermissions::Media, true));

            $group->get('/streamers', Controller\Stations\StreamersAction::class)
                ->setName('stations:streamers:index')
                ->add(new Middleware\StationSupportsFeature(StationFeatures::Streamers))
                ->add(new Middleware\Permissions(StationPermissions::Streamers, true));

            $group->get('/webhooks', Controller\Stations\WebhooksAction::class)
                ->setName('stations:webhooks:index')
                ->add(new Middleware\StationSupportsFeature(StationFeatures::Webhooks))
                ->add(new Middleware\Permissions(StationPermissions::WebHooks, true));
        }
    )
        ->add(Middleware\Module\Stations::class)
        ->add(new Middleware\Permissions(StationPermissions::View, true))
        ->add(Middleware\RequireStation::class)
        ->add(Middleware\GetStation::class)
        ->add(Middleware\EnableView::class)
        ->add(Middleware\RequireLogin::class);
};
