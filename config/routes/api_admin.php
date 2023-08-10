<?php

declare(strict_types=1);

use App\Controller;
use App\Enums\GlobalPermissions;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Middleware;
use Slim\Routing\RouteCollectorProxy;

return static function (RouteCollectorProxy $group) {
    $group->group(
        '/admin',
        function (RouteCollectorProxy $group) {
            $group->group(
                '',
                function (RouteCollectorProxy $group) {
                    $group->get(
                        '/api-keys',
                        Controller\Api\Admin\ApiKeysController::class . ':listAction'
                    )->setName('api:admin:api-keys');

                    $group->get(
                        '/api-key/{id}',
                        Controller\Api\Admin\ApiKeysController::class . ':getAction'
                    )->setName('api:admin:api-key');

                    $group->delete(
                        '/api-key/{id}',
                        Controller\Api\Admin\ApiKeysController::class . ':deleteAction'
                    );
                }
            )->add(new Middleware\Permissions(GlobalPermissions::ApiKeys));

            $group->get('/auditlog', Controller\Api\Admin\AuditLogAction::class)
                ->setName('api:admin:auditlog')
                ->add(new Middleware\Permissions(GlobalPermissions::Logs));

            $group->group(
                '/backups',
                function (RouteCollectorProxy $group) {
                    $group->get('', Controller\Api\Admin\Backups\GetAction::class)
                        ->setName('api:admin:backups');

                    $group->post('/run', Controller\Api\Admin\Backups\RunAction::class)
                        ->setName('api:admin:backups:run');

                    $group->get('/log/{path}', Controller\Api\Admin\Backups\GetLogAction::class)
                        ->setName('api:admin:backups:log');

                    $group->get('/download/{path}', Controller\Api\Admin\Backups\DownloadAction::class)
                        ->setName('api:admin:backups:download');

                    $group->delete('/delete/{path}', Controller\Api\Admin\Backups\DeleteAction::class)
                        ->setName('api:admin:backups:delete');
                }
            )->add(new Middleware\Permissions(GlobalPermissions::Backups));

            $group->group(
                '/debug',
                function (RouteCollectorProxy $group) {
                    $group->put('/clear-cache', Controller\Api\Admin\Debug\ClearCacheAction::class)
                        ->setName('api:admin:debug:clear-cache');

                    $group->put(
                        '/clear-queue[/{queue}]',
                        Controller\Api\Admin\Debug\ClearQueueAction::class
                    )->setName('api:admin:debug:clear-queue');

                    $group->put('/sync/{task}', Controller\Api\Admin\Debug\SyncAction::class)
                        ->setName('api:admin:debug:sync');

                    $group->group(
                        '/station/{station_id}',
                        function (RouteCollectorProxy $group) {
                            $group->put(
                                '/nowplaying',
                                Controller\Api\Admin\Debug\NowPlayingAction::class
                            )->setName('api:admin:debug:nowplaying');

                            $group->put(
                                '/nextsong',
                                Controller\Api\Admin\Debug\NextSongAction::class
                            )->setName('api:admin:debug:nextsong');

                            $group->put(
                                '/clearqueue',
                                Controller\Api\Admin\Debug\ClearStationQueueAction::class
                            )->setName('api:admin:debug:clear-station-queue');

                            $group->put('/telnet', Controller\Api\Admin\Debug\TelnetAction::class)
                                ->setName('api:admin:debug:telnet');
                        }
                    )->add(Middleware\GetStation::class);
                }
            )->add(new Middleware\Permissions(GlobalPermissions::All));

            $group->get('/server/stats', Controller\Api\Admin\ServerStatsAction::class)
                ->setName('api:admin:server:stats')
                ->add(new Middleware\Permissions(GlobalPermissions::View));

            $group->get(
                '/services',
                Controller\Api\Admin\ServiceControlController::class . ':getAction'
            )->setName('api:admin:services')
                ->add(new Middleware\Permissions(GlobalPermissions::View));

            $group->post(
                '/services/restart/{service}',
                Controller\Api\Admin\ServiceControlController::class . ':restartAction'
            )->setName('api:admin:services:restart')
                ->add(new Middleware\Permissions(GlobalPermissions::All));

            $group->get('/permissions', Controller\Api\Admin\PermissionsAction::class)
                ->add(new Middleware\Permissions(GlobalPermissions::All));

            $group->get('/relays/list', Controller\Api\Admin\RelaysAction::class)
                ->setName('api:admin:relays')
                ->add(new Middleware\Permissions(GlobalPermissions::Stations));

            $group->map(
                ['GET', 'POST'],
                '/relays',
                function (ServerRequest $request, Response $response) {
                    return $response->withRedirect(
                        $request->getRouter()->fromHere('api:internal:relays')
                    );
                }
            );

            $group->group(
                '',
                function (RouteCollectorProxy $group) {
                    $group->get(
                        '/settings[/{group}]',
                        Controller\Api\Admin\SettingsController::class . ':listAction'
                    )->setName('api:admin:settings');

                    $group->put(
                        '/settings[/{group}]',
                        Controller\Api\Admin\SettingsController::class . ':updateAction'
                    );

                    $group->post(
                        '/send-test-message',
                        Controller\Api\Admin\SendTestMessageAction::class
                    )->setName('api:admin:send-test-message');

                    $group->put(
                        '/acme',
                        Controller\Api\Admin\Acme\GenerateCertificateAction::class
                    )->setName('api:admin:acme');

                    $group->get(
                        '/acme-log/{path}',
                        Controller\Api\Admin\Acme\CertificateLogAction::class
                    )->setName('api:admin:acme-log');

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

                    $group->get(
                        '/geolite',
                        Controller\Api\Admin\GeoLite\GetAction::class
                    )->setName('api:admin:geolite');

                    $group->post(
                        '/geolite',
                        Controller\Api\Admin\GeoLite\PostAction::class
                    );

                    $group->get(
                        '/shoutcast',
                        Controller\Api\Admin\Shoutcast\GetAction::class
                    )->setName('api:admin:shoutcast');

                    $group->post(
                        '/shoutcast',
                        Controller\Api\Admin\Shoutcast\PostAction::class
                    );

                    $group->get(
                        '/stereo_tool',
                        Controller\Api\Admin\StereoTool\GetAction::class
                    )->setName('api:admin:stereo_tool');

                    $group->post(
                        '/stereo_tool',
                        Controller\Api\Admin\StereoTool\PostAction::class
                    );

                    $group->delete(
                        '/stereo_tool',
                        Controller\Api\Admin\StereoTool\DeleteAction::class
                    );
                }
            )->add(new Middleware\Permissions(GlobalPermissions::Settings));

            $adminApiEndpoints = [
                [
                    'custom_field',
                    'custom_fields',
                    Controller\Api\Admin\CustomFieldsController::class,
                    GlobalPermissions::CustomFields,
                ],
                ['role', 'roles', Controller\Api\Admin\RolesController::class, GlobalPermissions::All],
                ['station', 'stations', Controller\Api\Admin\StationsController::class, GlobalPermissions::Stations],
                ['user', 'users', Controller\Api\Admin\UsersController::class, GlobalPermissions::All],
                [
                    'storage_location',
                    'storage_locations',
                    Controller\Api\Admin\StorageLocationsController::class,
                    GlobalPermissions::StorageLocations,
                ],
            ];

            foreach ($adminApiEndpoints as [$singular, $plural, $class, $permission]) {
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

            $group->post('/station/{id}/clone', Controller\Api\Admin\Stations\CloneAction::class)
                ->setName('api:admin:station:clone')
                ->add(new Middleware\Permissions(GlobalPermissions::Stations));

            $group->get(
                '/stations/storage-locations',
                Controller\Api\Admin\Stations\StorageLocationsAction::class
            )->setName('api:admin:stations:storage-locations')
                ->add(new Middleware\Permissions(GlobalPermissions::Stations));

            $group->group(
                '',
                function (RouteCollectorProxy $group) {
                    $group->get('/logs', Controller\Api\Admin\LogsAction::class)
                        ->setName('api:admin:logs');

                    $group->get('/log/{log}', Controller\Api\Admin\LogsAction::class)
                        ->setName('api:admin:log');
                }
            )->add(new Middleware\Permissions(GlobalPermissions::Logs));

            $group->group(
                '/updates',
                function (RouteCollectorProxy $group) {
                    $group->get('', Controller\Api\Admin\Updates\GetUpdatesAction::class)
                        ->setName('api:admin:updates');

                    $group->put('', Controller\Api\Admin\Updates\PutUpdatesAction::class);
                }
            )->add(new Middleware\Permissions(GlobalPermissions::All));

            call_user_func(include(__DIR__ . '/api_admin_vue.php'), $group);
        }
    );
};
