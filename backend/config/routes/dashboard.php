<?php

declare(strict_types=1);

use App\Controller\Frontend\DashboardAction;
use App\Middleware;
use Slim\Routing\RouteCollectorProxy;

return static function (RouteCollectorProxy $app) {
    // All routes handled by the "Dashboard" handler (which renders the Vue SPA)
    $app->group(
        '',
        function (RouteCollectorProxy $group) {
            $routes = [
                'dashboard' => '/dashboard',
                'profile:index' => '/profile',
            ];

            foreach ($routes as $routeName => $routePath) {
                $group->get($routePath, DashboardAction::class)
                    ->setName($routeName);
            }

            $group->group(
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
                        'stations:podcast:episodes' => '/podcast/{podcast_id}',
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
                        $group->get($routePath, DashboardAction::class)
                            ->setName($routeName);
                    }

                    $group->get('/{routes:.+}', DashboardAction::class);
                }
            );

            $group->group(
                '/admin',
                function (RouteCollectorProxy $group) {
                    $routes = [
                        'admin:index:index' => '',
                        'admin:debug:index' => '/debug',
                        'admin:install_shoutcast:index' => '/install/shoutcast',
                        'admin:install_stereo_tool:index' => '/install/stereo_tool',
                        'admin:install_geolite:index' => '/install/geolite',
                        'admin:auditlog:index' => '/auditlog',
                        'admin:api:index' => '/api-keys',
                        'admin:backups:index' => '/backups',
                        'admin:branding:index' => '/branding',
                        'admin:custom_fields:index' => '/custom_fields',
                        'admin:logs:index' => '/logs',
                        'admin:permissions:index' => '/permissions',
                        'admin:relays:index' => '/relays',
                        'admin:settings:index' => '/settings',
                        'admin:stations:index' => '/stations',
                        'admin:storage_locations:index' => '/storage_locations',
                        'admin:updates:index' => '/updates',
                        'admin:users:index' => '/users',
                    ];

                    foreach ($routes as $routeName => $routePath) {
                        $group->get($routePath, DashboardAction::class)
                            ->setName($routeName);
                    }

                    $group->get('/{routes:.+}', DashboardAction::class);
                }
            );
        }
    )->add(Middleware\EnableView::class)
        ->add(Middleware\RequireLogin::class);
};
