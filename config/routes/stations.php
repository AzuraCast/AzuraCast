<?php

declare(strict_types=1);

use App\Controller\Stations\IndexAction;
use App\Enums\StationPermissions;
use App\Middleware;
use Slim\Routing\RouteCollectorProxy;

return static function (RouteCollectorProxy $app) {
    $app->group(
        '/station/{station_id}',
        function (RouteCollectorProxy $group) {
            $routes = [
                'stations:index:index' => '',
                'stations:branding' => '/branding',
                'stations:bulk-media' => '/bulk-media',
                'stations:fallback' => '/fallback',
                'stations:files:index' => '/files[/{fspath}]',
                'stations:hls_streams:index' => '/hls_streams',
                'stations:util:ls_config' => '/ls_config',
                'stations:stereo_tool_config' => '/stereo_tool_config',
                'stations:logs' => '/logs',
                'stations:playlists:index' => '/playlists',
                'stations:podcasts:index' => '/podcasts',
                'stations:mounts:index' => '/mounts',
                'stations:profile:index' => '/profile',
                'stations:profile:edit' => '/profile/edit',
                'stations:queue:index' => '/queue',
                'stations:remotes:index' => '/remotes',
                'stations:reports:overview' => '/reports/overview',
                'stations:reports:timeline' => '/reports/timeline',
                'stations:reports:listeners' => '/reports/listeners',
                'stations:reports:soundexchange' => '/reports/soundexchange',
                'stations:reports:requests' => '/reports/requests',
                'stations:restart:index' => '/restart',
                'stations:sftp_users:index' => '/sftp_users',
                'stations:streamers:index' => '/streamers',
                'stations:webhooks:index' => '/webhooks',
            ];

            foreach ($routes as $routeName => $routePath) {
                $group->get($routePath, IndexAction::class)
                    ->setName($routeName);
            }

            $group->get('/{routes:.+}', IndexAction::class);
        }
    )->add(Middleware\Module\PanelLayout::class)
        ->add(new Middleware\Permissions(StationPermissions::View, true))
        ->add(Middleware\EnableView::class)
        ->add(Middleware\RequireStation::class)
        ->add(Middleware\GetStation::class)
        ->add(Middleware\RequireLogin::class);
};
