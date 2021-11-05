<?php

use App\Acl;
use App\Controller;
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
                function (ServerRequest $request, Response $response) {
                    return $response->withRedirect(
                        (string)$request->getRouter()->fromHere('stations:profile:index')
                    );
                }
            )->setName('stations:index:index');

            $group->get(
                '/automation',
                Controller\Stations\AutomationAction::class
            )->setName('stations:automation:index')
                ->add(new Middleware\Permissions(Acl::STATION_AUTOMATION, true));

            $group->get('/files', Controller\Stations\FilesAction::class)
                ->setName('stations:files:index')
                ->add(new Middleware\Permissions(Acl::STATION_MEDIA, true));

            $group->get('/ls_config', Controller\Stations\EditLiquidsoapConfigAction::class)
                ->setName('stations:util:ls_config')
                ->add(new Middleware\Permissions(Acl::STATION_BROADCASTING, true));

            $group->group(
                '/logs',
                function (RouteCollectorProxy $group) {
                    $group->get('', Controller\Stations\LogsController::class)
                        ->setName('stations:logs:index');

                    $group->get('/view/{log}', Controller\Stations\LogsController::class . ':viewAction')
                        ->setName('stations:logs:view');
                }
            )->add(new Middleware\Permissions(Acl::STATION_LOGS, true));

            $group->get('/playlists', Controller\Stations\PlaylistsAction::class)
                ->setName('stations:playlists:index')
                ->add(new Middleware\Permissions(Acl::STATION_MEDIA, true));

            $group->get('/podcasts', Controller\Stations\PodcastsAction::class)
                ->setName('stations:podcasts:index')
                ->add(new Middleware\Permissions(Acl::STATION_PODCASTS, true));

            $group->get('/mounts', Controller\Stations\MountsAction::class)
                ->setName('stations:mounts:index')
                ->add(new Middleware\Permissions(Acl::STATION_MOUNTS, true));

            $group->get('/profile', Controller\Stations\ProfileController::class)
                ->setName('stations:profile:index');

            $group->get(
                '/profile/toggle/{feature}/{csrf}',
                Controller\Stations\ProfileController::class . ':toggleAction'
            )
                ->setName('stations:profile:toggle')
                ->add(new Middleware\Permissions(Acl::STATION_PROFILE, true));

            $group->get('/profile/edit', Controller\Stations\ProfileController::class . ':editAction')
                ->setName('stations:profile:edit')
                ->add(new Middleware\Permissions(Acl::STATION_PROFILE, true));

            $group->get('/queue', Controller\Stations\QueueAction::class)
                ->setName('stations:queue:index')
                ->add(new Middleware\Permissions(Acl::STATION_BROADCASTING, true));

            $group->get('/remotes', Controller\Stations\RemotesAction::class)
                ->setName('stations:remotes:index')
                ->add(new Middleware\Permissions(Acl::STATION_REMOTES, true));

            $group->group(
                '/reports',
                function (RouteCollectorProxy $group) {
                    $group->get('/overview', Controller\Stations\Reports\OverviewAction::class)
                        ->setName('stations:reports:overview');

                    $group->get('/timeline', Controller\Stations\Reports\TimelineAction::class)
                        ->setName('stations:reports:timeline');

                    $group->get(
                        '/performance',
                        Controller\Stations\Reports\PerformanceAction::class
                    )->setName('stations:reports:performance');

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
            )->add(new Middleware\Permissions(Acl::STATION_REPORTS, true));

            $group->get('/sftp_users', Controller\Stations\SftpUsersAction::class)
                ->setName('stations:sftp_users:index')
                ->add(new Middleware\Permissions(Acl::STATION_MEDIA, true));

            $group->get('/streamers', Controller\Stations\StreamersAction::class)
                ->setName('stations:streamers:index')
                ->add(new Middleware\Permissions(Acl::STATION_STREAMERS, true));

            $group->get('/webhooks', Controller\Stations\WebhooksAction::class)
                ->setName('stations:webhooks:index')
                ->add(new Middleware\Permissions(Acl::STATION_WEB_HOOKS, true));
        }
    )
        ->add(Middleware\Module\Stations::class)
        ->add(new Middleware\Permissions(Acl::STATION_VIEW, true))
        ->add(Middleware\RequireStation::class)
        ->add(Middleware\GetStation::class)
        ->add(Middleware\EnableView::class)
        ->add(Middleware\RequireLogin::class);
};
