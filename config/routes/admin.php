<?php

use App\Acl;
use App\Controller;
use App\Middleware;
use Slim\Routing\RouteCollectorProxy;

return static function (RouteCollectorProxy $app) {
    $app->group(
        '/admin',
        function (RouteCollectorProxy $group) {
            $group->get('', Controller\Admin\IndexController::class)
                ->setName('admin:index:index');

            $group->group(
                '/debug',
                function (RouteCollectorProxy $group) {
                    $group->get('', Controller\Admin\DebugController::class)
                        ->setName('admin:debug:index');

                    $group->get('/clear-cache', Controller\Admin\DebugController::class . ':clearCacheAction')
                        ->setName('admin:debug:clear-cache');

                    $group->get(
                        '/clear-queue[/{queue}]',
                        Controller\Admin\DebugController::class . ':clearQueueAction'
                    )
                        ->setName('admin:debug:clear-queue');

                    $group->get('/sync/{type}', Controller\Admin\DebugController::class . ':syncAction')
                        ->setName('admin:debug:sync');

                    $group->get('/log/{path}', Controller\Admin\DebugController::class . ':logAction')
                        ->setName('admin:debug:log');

                    $group->group(
                        '/station/{station_id}',
                        function (RouteCollectorProxy $group) {
                            $group->map(
                                ['GET', 'POST'],
                                '/nextsong',
                                Controller\Admin\DebugController::class . ':nextsongAction'
                            )
                                ->setName('admin:debug:nextsong');

                            $group->post('/telnet', Controller\Admin\DebugController::class . ':telnetAction')
                                ->setName('admin:debug:telnet');
                        }
                    )->add(Middleware\GetStation::class);
                }
            )->add(new Middleware\Permissions(Acl::GLOBAL_ALL));

            $group->group(
                '/install',
                function (RouteCollectorProxy $group) {
                    $group->get('/shoutcast', Controller\Admin\ShoutcastAction::class)
                        ->setName('admin:install_shoutcast:index');

                    $group->get('/geolite', Controller\Admin\GeoLiteAction::class)
                        ->setName('admin:install_geolite:index');
                }
            )->add(new Middleware\Permissions(Acl::GLOBAL_SETTINGS));

            $group->get('/auditlog', Controller\Admin\AuditLogAction::class)
                ->setName('admin:auditlog:index')
                ->add(new Middleware\Permissions(Acl::GLOBAL_LOGS));

            $group->group(
                '/api',
                function (RouteCollectorProxy $group) {
                    $group->get('', Controller\Admin\ApiController::class . ':indexAction')
                        ->setName('admin:api:index');

                    $group->map(['GET', 'POST'], '/edit/{id}', Controller\Admin\ApiController::class . ':editAction')
                        ->setName('admin:api:edit');

                    $group->get('/delete/{id}/{csrf}', Controller\Admin\ApiController::class . ':deleteAction')
                        ->setName('admin:api:delete');
                }
            )->add(new Middleware\Permissions(Acl::GLOBAL_API_KEYS));

            $group->group(
                '/backups',
                function (RouteCollectorProxy $group) {
                    $group->get('', Controller\Admin\BackupsController::class)
                        ->setName('admin:backups:index');

                    $group->map(
                        ['GET', 'POST'],
                        '/configure',
                        Controller\Admin\BackupsController::class . ':configureAction'
                    )
                        ->setName('admin:backups:configure');

                    $group->map(['GET', 'POST'], '/run', Controller\Admin\BackupsController::class . ':runAction')
                        ->setName('admin:backups:run');

                    $group->get('/log/{path}', Controller\Admin\BackupsController::class . ':logAction')
                        ->setName('admin:backups:log');

                    $group->get('/download/{path}', Controller\Admin\BackupsController::class . ':downloadAction')
                        ->setName('admin:backups:download');

                    $group->get('/delete/{path}/{csrf}', Controller\Admin\BackupsController::class . ':deleteAction')
                        ->setName('admin:backups:delete');
                }
            )->add(new Middleware\Permissions(Acl::GLOBAL_BACKUPS));

            $group->get('/branding', Controller\Admin\BrandingAction::class)
                ->setName('admin:branding:index')
                ->add(new Middleware\Permissions(Acl::GLOBAL_SETTINGS));

            $group->get('/custom_fields', Controller\Admin\CustomFieldsAction::class)
                ->setName('admin:custom_fields:index')
                ->add(new Middleware\Permissions(Acl::GLOBAL_CUSTOM_FIELDS));

            $group->group(
                '/logs',
                function (RouteCollectorProxy $group) {
                    $group->get('', Controller\Admin\LogsController::class)
                        ->setName('admin:logs:index');

                    $group->get('/view/{station_id}/{log}', Controller\Admin\LogsController::class . ':viewAction')
                        ->setName('admin:logs:view')
                        ->add(Middleware\GetStation::class);
                }
            )->add(new Middleware\Permissions(Acl::GLOBAL_LOGS));

            $group->get('/permissions', Controller\Admin\PermissionsAction::class)
                ->setName('admin:permissions:index')
                ->add(new Middleware\Permissions(Acl::GLOBAL_ALL));

            $group->get('/relays', Controller\Admin\RelaysController::class)
                ->setName('admin:relays:index')
                ->add(new Middleware\Permissions(Acl::GLOBAL_STATIONS));

            $group->map(['GET', 'POST'], '/settings', Controller\Admin\SettingsAction::class)
                ->setName('admin:settings:index')
                ->add(new Middleware\Permissions(Acl::GLOBAL_SETTINGS));

            $group->get('/stations', Controller\Admin\StationsAction::class)
                ->setName('admin:stations:index')
                ->add(new Middleware\Permissions(Acl::GLOBAL_STATIONS));

            $group->get('/storage_locations', Controller\Admin\StorageLocationsAction::class)
                ->setName('admin:storage_locations:index')
                ->add(new Middleware\Permissions(Acl::GLOBAL_STORAGE_LOCATIONS));

            $group->group(
                '/users',
                function (RouteCollectorProxy $group) {
                    $group->get('', Controller\Admin\UsersController::class . ':indexAction')
                        ->setName('admin:users:index');

                    $group->map(['GET', 'POST'], '/edit/{id}', Controller\Admin\UsersController::class . ':editAction')
                        ->setName('admin:users:edit');

                    $group->map(['GET', 'POST'], '/add', Controller\Admin\UsersController::class . ':editAction')
                        ->setName('admin:users:add');

                    $group->get('/delete/{id}/{csrf}', Controller\Admin\UsersController::class . ':deleteAction')
                        ->setName('admin:users:delete');

                    $group->get('/login-as/{id}/{csrf}', Controller\Admin\UsersController::class . ':impersonateAction')
                        ->setName('admin:users:impersonate');
                }
            )->add(new Middleware\Permissions(Acl::GLOBAL_ALL));
        }
    )
        ->add(Middleware\Module\Admin::class)
        ->add(Middleware\EnableView::class)
        ->add(new Middleware\Permissions(Acl::GLOBAL_VIEW))
        ->add(Middleware\RequireLogin::class);
};
