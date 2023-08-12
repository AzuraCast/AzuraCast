<?php

declare(strict_types=1);

use App\Controller\Admin\IndexAction;
use App\Enums\GlobalPermissions;
use App\Middleware;
use Slim\Routing\RouteCollectorProxy;

return static function (RouteCollectorProxy $app) {
    $app->group(
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
                $group->get($routePath, IndexAction::class)
                    ->setName($routeName);
            }

            $group->get('/{routes:.+}', IndexAction::class);
        }
    )->add(Middleware\Module\PanelLayout::class)
        ->add(Middleware\EnableView::class)
        ->add(new Middleware\Permissions(GlobalPermissions::View))
        ->add(Middleware\RequireLogin::class);
};
