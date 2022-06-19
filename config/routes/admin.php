<?php

use App\Controller;
use App\Enums\GlobalPermissions;
use App\Middleware;
use Slim\Routing\RouteCollectorProxy;

return static function (RouteCollectorProxy $app) {
    $app->group(
        '/admin',
        function (RouteCollectorProxy $group) {
            $group->get('', Controller\Admin\IndexAction::class)
                ->setName('admin:index:index');

            $group->group(
                '/debug',
                function (RouteCollectorProxy $group) {
                    $group->get('', Controller\Admin\Debug\IndexAction::class)
                        ->setName('admin:debug:index');

                    $group->get('/clear-cache', Controller\Admin\Debug\ClearCacheAction::class)
                        ->setName('admin:debug:clear-cache');

                    $group->get(
                        '/clear-queue[/{queue}]',
                        Controller\Admin\Debug\ClearQueueAction::class
                    )->setName('admin:debug:clear-queue');

                    $group->get('/sync/{task}', Controller\Admin\Debug\SyncAction::class)
                        ->setName('admin:debug:sync');

                    $group->group(
                        '/station/{station_id}',
                        function (RouteCollectorProxy $group) {
                            $group->map(
                                ['GET', 'POST'],
                                '/nowplaying',
                                Controller\Admin\Debug\NowPlayingAction::class
                            )->setName('admin:debug:nowplaying');

                            $group->map(
                                ['GET', 'POST'],
                                '/nextsong',
                                Controller\Admin\Debug\NextSongAction::class
                            )->setName('admin:debug:nextsong');

                            $group->map(
                                ['GET', 'POST'],
                                '/clearqueue',
                                Controller\Admin\Debug\ClearStationQueueAction::class
                            )->setName('admin:debug:clear-station-queue');

                            $group->post('/telnet', Controller\Admin\Debug\TelnetAction::class)
                                ->setName('admin:debug:telnet');
                        }
                    )->add(Middleware\GetStation::class);
                }
            )->add(new Middleware\Permissions(GlobalPermissions::All));

            $group->group(
                '/install',
                function (RouteCollectorProxy $group) {
                    $group->get('/shoutcast', Controller\Admin\ShoutcastAction::class)
                        ->setName('admin:install_shoutcast:index');

                    $group->get('/stereo_tool', Controller\Admin\StereoToolAction::class)
                        ->setName('admin:install_stereo_tool:index');

                    $group->get('/geolite', Controller\Admin\GeoLiteAction::class)
                        ->setName('admin:install_geolite:index');
                }
            )->add(new Middleware\Permissions(GlobalPermissions::Settings));

            $group->get('/auditlog', Controller\Admin\AuditLogAction::class)
                ->setName('admin:auditlog:index')
                ->add(new Middleware\Permissions(GlobalPermissions::Logs));

            $group->get('/api-keys', Controller\Admin\ApiKeysAction::class)
                ->setName('admin:api:index')
                ->add(new Middleware\Permissions(GlobalPermissions::ApiKeys));

            $group->get('/backups', Controller\Admin\BackupsAction::class)
                ->setName('admin:backups:index')
                ->add(new Middleware\Permissions(GlobalPermissions::Backups));

            $group->get('/branding', Controller\Admin\BrandingAction::class)
                ->setName('admin:branding:index')
                ->add(new Middleware\Permissions(GlobalPermissions::Settings));

            $group->get('/custom_fields', Controller\Admin\CustomFieldsAction::class)
                ->setName('admin:custom_fields:index')
                ->add(new Middleware\Permissions(GlobalPermissions::CustomFields));

            $group->get('/logs', Controller\Admin\LogsAction::class)
                ->setName('admin:logs:index')
                ->add(new Middleware\Permissions(GlobalPermissions::Logs));

            $group->get('/permissions', Controller\Admin\PermissionsAction::class)
                ->setName('admin:permissions:index')
                ->add(new Middleware\Permissions(GlobalPermissions::All));

            $group->get('/relays', Controller\Admin\RelaysAction::class)
                ->setName('admin:relays:index')
                ->add(new Middleware\Permissions(GlobalPermissions::Stations));

            $group->map(['GET', 'POST'], '/settings', Controller\Admin\SettingsAction::class)
                ->setName('admin:settings:index')
                ->add(new Middleware\Permissions(GlobalPermissions::Settings));

            $group->get('/stations', Controller\Admin\StationsAction::class)
                ->setName('admin:stations:index')
                ->add(new Middleware\Permissions(GlobalPermissions::Stations));

            $group->get('/storage_locations', Controller\Admin\StorageLocationsAction::class)
                ->setName('admin:storage_locations:index')
                ->add(new Middleware\Permissions(GlobalPermissions::StorageLocations));

            $group->get('/users', Controller\Admin\UsersAction::class)
                ->setName('admin:users:index')
                ->add(new Middleware\Permissions(GlobalPermissions::All));
        }
    )
        ->add(Middleware\Module\Admin::class)
        ->add(Middleware\EnableView::class)
        ->add(new Middleware\Permissions(GlobalPermissions::View))
        ->add(Middleware\RequireLogin::class);
};
