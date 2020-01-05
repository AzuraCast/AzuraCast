<?php

use App\Acl;
use App\Controller;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Middleware;
use Azura\Middleware as AzuraMiddleware;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app) {
    $app->group('/admin', function (RouteCollectorProxy $group) {
        $group->get('', Controller\Admin\IndexController::class . ':indexAction')
            ->setName('admin:index:index');

        $group->get('/sync/{type}', Controller\Admin\IndexController::class . ':syncAction')
            ->setName('admin:index:sync')
            ->add(new Middleware\Permissions(Acl::GLOBAL_ALL));

        $group->group('/install', function (RouteCollectorProxy $group) {

            $group->map(['GET', 'POST'], '/shoutcast', Controller\Admin\InstallShoutcastController::class)
                ->setName('admin:install:shoutcast');

            $group->map(['GET', 'POST'], '/geolite', Controller\Admin\InstallGeoLiteController::class)
                ->setName('admin:install:geolite');

        })->add(new Middleware\Permissions(Acl::GLOBAL_ALL));

        $group->get('/auditlog', Controller\Admin\AuditLogController::class)
            ->setName('admin:auditlog:index')
            ->add(new Middleware\Permissions(Acl::GLOBAL_LOGS));

        $group->group('/api', function (RouteCollectorProxy $group) {

            $group->get('', Controller\Admin\ApiController::class . ':indexAction')
                ->setName('admin:api:index');

            $group->map(['GET', 'POST'], '/edit/{id}', Controller\Admin\ApiController::class . ':editAction')
                ->setName('admin:api:edit');

            $group->get('/delete/{id}/{csrf}', Controller\Admin\ApiController::class . ':deleteAction')
                ->setName('admin:api:delete');

        })->add(new Middleware\Permissions(Acl::GLOBAL_API_KEYS));

        $group->group('/backups', function (RouteCollectorProxy $group) {

            $group->get('', Controller\Admin\BackupsController::class)
                ->setName('admin:backups:index');

            $group->map(['GET', 'POST'], '/configure', Controller\Admin\BackupsController::class . ':configureAction')
                ->setName('admin:backups:configure');

            $group->map(['GET', 'POST'], '/run', Controller\Admin\BackupsController::class . ':runAction')
                ->setName('admin:backups:run');

            $group->get('/delete/{path}', Controller\Admin\BackupsController::class . ':downloadAction')
                ->setName('admin:backups:download');

            $group->get('/delete/{path}/{csrf}', Controller\Admin\BackupsController::class . ':deleteAction')
                ->setName('admin:backups:delete');

        })->add(new Middleware\Permissions(Acl::GLOBAL_BACKUPS));

        $group->map(['GET', 'POST'], '/branding', Controller\Admin\BrandingController::class)
            ->setName('admin:branding:index')
            ->add(new Middleware\Permissions(Acl::GLOBAL_SETTINGS));

        $group->group('/custom_fields', function (RouteCollectorProxy $group) {

            $group->get('', Controller\Admin\CustomFieldsController::class . ':indexAction')
                ->setName('admin:custom_fields:index');

            $group->map(['GET', 'POST'], '/edit/{id}', Controller\Admin\CustomFieldsController::class . ':editAction')
                ->setName('admin:custom_fields:edit');

            $group->map(['GET', 'POST'], '/add', Controller\Admin\CustomFieldsController::class . ':editAction')
                ->setName('admin:custom_fields:add');

            $group->get('/delete/{id}/{csrf}', Controller\Admin\CustomFieldsController::class . ':deleteAction')
                ->setName('admin:custom_fields:delete');

        })->add(new Middleware\Permissions(Acl::GLOBAL_CUSTOM_FIELDS));

        $group->group('/logs', function (RouteCollectorProxy $group) {

            $group->get('', Controller\Admin\LogsController::class)
                ->setName('admin:logs:index');

            $group->get('/view/{station_id}/{log}', Controller\Admin\LogsController::class . ':viewAction')
                ->setName('admin:logs:view')
                ->add(Middleware\GetStation::class);

        })->add(new Middleware\Permissions(Acl::GLOBAL_LOGS));

        $group->group('/permissions', function (RouteCollectorProxy $group) {

            $group->get('', Controller\Admin\PermissionsController::class . ':indexAction')
                ->setName('admin:permissions:index');

            $group->map(['GET', 'POST'], '/edit/{id}', Controller\Admin\PermissionsController::class . ':editAction')
                ->setName('admin:permissions:edit');

            $group->map(['GET', 'POST'], '/add', Controller\Admin\PermissionsController::class . ':editAction')
                ->setName('admin:permissions:add');

            $group->get('/delete/{id}/{csrf}', Controller\Admin\PermissionsController::class . ':deleteAction')
                ->setName('admin:permissions:delete');

        })->add(new Middleware\Permissions(Acl::GLOBAL_PERMISSIONS));

        $group->get('/relays', Controller\Admin\RelaysController::class)
            ->setName('admin:relays:index')
            ->add(new Middleware\Permissions(Acl::GLOBAL_STATIONS));

        $group->map(['GET', 'POST'], '/settings', Controller\Admin\SettingsController::class)
            ->setName('admin:settings:index')
            ->add(new Middleware\Permissions(Acl::GLOBAL_SETTINGS));

        $group->group('/stations', function (RouteCollectorProxy $group) {

            $group->get('', Controller\Admin\StationsController::class)
                ->setName('admin:stations:index');

            $group->map(['GET', 'POST'], '/edit/{id}', Controller\Admin\StationsController::class . ':editAction')
                ->setName('admin:stations:edit');

            $group->map(['GET', 'POST'], '/add', Controller\Admin\StationsController::class . ':editAction')
                ->setName('admin:stations:add');

            $group->map(['GET', 'POST'], '/clone/{id}', Controller\Admin\StationsController::class . ':cloneAction')
                ->setName('admin:stations:clone');

            $group->get('/delete/{id}/{csrf}', Controller\Admin\StationsController::class . ':deleteAction')
                ->setName('admin:stations:delete');

        })->add(new Middleware\Permissions(Acl::GLOBAL_STATIONS));

        $group->group('/users', function (RouteCollectorProxy $group) {

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

        })->add(new Middleware\Permissions(Acl::GLOBAL_USERS));

        // END /admin GROUP

    })
        ->add(Middleware\Module\Admin::class)
        ->add(AzuraMiddleware\EnableView::class)
        ->add(new Middleware\Permissions(Acl::GLOBAL_VIEW))
        ->add(Middleware\RequireLogin::class);

    $app->group('/api', function (RouteCollectorProxy $group) {

        $group->options('/{routes:.+}', function (ServerRequest $request, Response $response) {
            return $response
                ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                ->withHeader('Access-Control-Allow-Headers',
                    'x-requested-with, Content-Type, Accept, Origin, Authorization')
                ->withHeader('Access-Control-Allow-Origin', '*');
        });

        $group->get('', Controller\Api\IndexController::class . ':indexAction')
            ->setName('api:index:index');

        $group->get('/openapi.yml', Controller\Api\OpenApiController::class)
            ->setName('api:openapi');

        $group->get('/status', Controller\Api\IndexController::class . ':statusAction')
            ->setName('api:index:status');

        $group->get('/time', Controller\Api\IndexController::class . ':timeAction')
            ->setName('api:index:time');

        $group->group('/internal', function (RouteCollectorProxy $group) {

            $group->group('/{station_id}', function (RouteCollectorProxy $group) {

                // Liquidsoap internal authentication functions
                $group->map(['GET', 'POST'], '/auth', Controller\Api\InternalController::class . ':authAction')
                    ->setName('api:internal:auth');

                $group->map(['GET', 'POST'], '/nextsong', Controller\Api\InternalController::class . ':nextsongAction')
                    ->setName('api:internal:nextsong');

                $group->map(['GET', 'POST'], '/djon', Controller\Api\InternalController::class . ':djonAction')
                    ->setName('api:internal:djon');

                $group->map(['GET', 'POST'], '/djoff', Controller\Api\InternalController::class . ':djoffAction')
                    ->setName('api:internal:djoff');

                $group->map(['GET', 'POST'], '/feedback', Controller\Api\InternalController::class . ':feedbackAction')
                    ->setName('api:internal:feedback');

            })->add(Middleware\GetStation::class);

            $group->get('/relays', Controller\Api\Admin\RelaysController::class)
                ->setName('api:internal:relays')
                ->add(Middleware\RequireLogin::class);

            $group->post('/relays', Controller\Api\Admin\RelaysController::class . ':updateAction')
                ->add(Middleware\RequireLogin::class);

        });

        $group->get('/nowplaying[/{station_id}]', Controller\Api\NowplayingController::class)
            ->setName('api:nowplaying:index');

        $group->get('/stations', Controller\Api\Stations\IndexController::class . ':listAction')
            ->setName('api:stations:list')
            ->add(new AzuraMiddleware\RateLimit('api'));

        $group->group('/admin', function (RouteCollectorProxy $group) {

            $group->get('/auditlog', Controller\Api\Admin\AuditLogController::class)
                ->setName('api:admin:auditlog')
                ->add(new Middleware\Permissions(Acl::GLOBAL_LOGS));

            $group->get('/permissions', Controller\Api\Admin\PermissionsController::class)
                ->add(new Middleware\Permissions(Acl::GLOBAL_PERMISSIONS));

            $group->map(['GET', 'POST'], '/relays', function (ServerRequest $request, Response $response) {
                return $response->withRedirect((string)$request->getRouter()->fromHere('api:internal:relays'));
            });

            $group->group('', function (RouteCollectorProxy $group) {
                $group->get('/settings', Controller\Api\Admin\SettingsController::class . ':listAction')
                    ->setName('api:admin:settings');

                $group->put('/settings', Controller\Api\Admin\SettingsController::class . ':updateAction');
            })->add(new Middleware\Permissions(Acl::GLOBAL_SETTINGS));

            $admin_api_endpoints = [
                [
                    'custom_field',
                    'custom_fields',
                    Controller\Api\Admin\CustomFieldsController::class,
                    Acl::GLOBAL_CUSTOM_FIELDS,
                ],
                ['role', 'roles', Controller\Api\Admin\RolesController::class, Acl::GLOBAL_PERMISSIONS],
                ['station', 'stations', Controller\Api\Admin\StationsController::class, Acl::GLOBAL_STATIONS],
                ['user', 'users', Controller\Api\Admin\UsersController::class, Acl::GLOBAL_USERS],
            ];

            foreach ($admin_api_endpoints as [$singular, $plural, $class, $permission]) {
                $group->group('', function (RouteCollectorProxy $group) use ($singular, $plural, $class) {
                    $group->get('/' . $plural, $class . ':listAction')
                        ->setName('api:admin:' . $plural);
                    $group->post('/' . $plural, $class . ':createAction');

                    $group->get('/' . $singular . '/{id}', $class . ':getAction')
                        ->setName('api:admin:' . $singular);
                    $group->put('/' . $singular . '/{id}', $class . ':editAction');
                    $group->delete('/' . $singular . '/{id}', $class . ':deleteAction');
                })->add(new Middleware\Permissions($permission));
            }
        });

        $group->group('/station/{station_id}', function (RouteCollectorProxy $group) {

            $group->get('', Controller\Api\Stations\IndexController::class . ':indexAction')
                ->setName('api:stations:index')
                ->add(new AzuraMiddleware\RateLimit('api', 5, 2));

            $group->get('/nowplaying', Controller\Api\NowplayingController::class . ':indexAction');

            $group->get('/history', Controller\Api\Stations\HistoryController::class)
                ->setName('api:stations:history')
                ->add(new Middleware\Permissions(Acl::STATION_REPORTS, true));

            $group->group('/queue', function (RouteCollectorProxy $group) {
                $group->get('', Controller\Api\Stations\QueueController::class . ':listAction')
                    ->setName('api:stations:queue');

                $group->get('/{id}', Controller\Api\Stations\QueueController::class . ':getAction')
                    ->setName('api:stations:queue:record');

                $group->delete('/{id}', Controller\Api\Stations\QueueController::class . ':deleteAction');
            })->add(new Middleware\Permissions(Acl::STATION_BROADCASTING, true));

            $group->get('/requests', Controller\Api\Stations\RequestsController::class . ':listAction')
                ->setName('api:requests:list');

            $group->map(['GET', 'POST'], '/request/{media_id}',
                Controller\Api\Stations\RequestsController::class . ':submitAction')
                ->setName('api:requests:submit')
                ->add(new AzuraMiddleware\RateLimit('api', 5, 2));

            $group->get('/listeners', Controller\Api\Stations\ListenersController::class . ':indexAction')
                ->setName('api:listeners:index')
                ->add(new Middleware\Permissions(Acl::STATION_REPORTS, true));

            $group->get('/art/{media_id:[a-zA-Z0-9\-]+}.jpg', Controller\Api\Stations\Art\GetArtAction::class)
                ->setName('api:stations:media:art');

            $group->get('/art/{media_id:[a-zA-Z0-9\-]+}', Controller\Api\Stations\Art\GetArtAction::class)
                ->setName('api:stations:media:art-internal');

            $group->post('/art/{media_id:[a-zA-Z0-9]+}', Controller\Api\Stations\Art\PostArtAction::class)
                ->add(new Middleware\Permissions(Acl::STATION_MEDIA, true));

            $group->delete('/art/{media_id:[a-zA-Z0-9]+}', Controller\Api\Stations\Art\DeleteArtAction::class)
                ->add(new Middleware\Permissions(Acl::STATION_MEDIA, true));

            $station_api_endpoints = [
                ['file', 'files', Controller\Api\Stations\FilesController::class, Acl::STATION_MEDIA],
                ['mount', 'mounts', Controller\Api\Stations\MountsController::class, Acl::STATION_MOUNTS],
                ['playlist', 'playlists', Controller\Api\Stations\PlaylistsController::class, Acl::STATION_MEDIA],
                ['remote', 'remotes', Controller\Api\Stations\RemotesController::class, Acl::STATION_REMOTES],
                ['streamer', 'streamers', Controller\Api\Stations\StreamersController::class, Acl::STATION_STREAMERS],
                ['webhook', 'webhooks', Controller\Api\Stations\WebhooksController::class, Acl::STATION_WEB_HOOKS],
            ];

            foreach ($station_api_endpoints as [$singular, $plural, $class, $permission]) {
                $group->group('', function (RouteCollectorProxy $group) use ($singular, $plural, $class) {
                    $group->get('/' . $plural, $class . ':listAction')
                        ->setName('api:stations:' . $plural);
                    $group->post('/' . $plural, $class . ':createAction');

                    $group->get('/' . $singular . '/{id}', $class . ':getAction')
                        ->setName('api:stations:' . $singular);
                    $group->put('/' . $singular . '/{id}', $class . ':editAction');
                    $group->delete('/' . $singular . '/{id}', $class . ':deleteAction');
                })->add(new Middleware\Permissions($permission, true));
            }

            $group->group('/files', function (RouteCollectorProxy $group) {

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

                $group->map(['GET', 'POST'], '/upload', Controller\Api\Stations\Files\UploadAction::class)
                    ->setName('api:stations:files:upload');

                $group->get('/download', Controller\Api\Stations\Files\DownloadAction::class)
                    ->setName('api:stations:files:download');

            })
                ->add(Middleware\Module\StationFiles::class)
                ->add(new Middleware\Permissions(Acl::STATION_MEDIA, true));

            $group->get('/playlists/schedule', Controller\Api\Stations\PlaylistsController::class . ':scheduleAction')
                ->setName('api:stations:playlists:schedule')
                ->add(new Middleware\Permissions(Acl::STATION_MEDIA, true));

            $group->group('/playlist/{id}', function (RouteCollectorProxy $group) {

                $group->put('/toggle', Controller\Api\Stations\PlaylistsController::class . ':toggleAction')
                    ->setName('api:stations:playlist:toggle');

                $group->get('/order', Controller\Api\Stations\PlaylistsController::class . ':getOrderAction')
                    ->setName('api:stations:playlist:order');

                $group->put('/order', Controller\Api\Stations\PlaylistsController::class . ':putOrderAction');

                $group->get('/export[/{format}]',
                    Controller\Api\Stations\PlaylistsController::class . ':exportAction')
                    ->setName('api:stations:playlist:export');

            })->add(new Middleware\Permissions(Acl::STATION_MEDIA, true));

            $group->get('/status', Controller\Api\Stations\ServicesController::class . ':statusAction')
                ->setName('api:stations:status')
                ->add(new Middleware\Permissions(Acl::STATION_VIEW, true));

            $group->post('/backend/{do}', Controller\Api\Stations\ServicesController::class . ':backendAction')
                ->setName('api:stations:backend')
                ->add(new Middleware\Permissions(Acl::STATION_BROADCASTING, true));

            $group->post('/frontend/{do}', Controller\Api\Stations\ServicesController::class . ':frontendAction')
                ->setName('api:stations:frontend')
                ->add(new Middleware\Permissions(Acl::STATION_BROADCASTING, true));

            $group->post('/restart', Controller\Api\Stations\ServicesController::class . ':restartAction')
                ->setName('api:stations:restart')
                ->add(new Middleware\Permissions(Acl::STATION_BROADCASTING, true));

        })->add(Middleware\RequireStation::class)
            ->add(Middleware\GetStation::class);

        // END /api GROUP

    })
        ->add(Middleware\Module\Api::class);

    $app->get('/', Controller\Frontend\IndexController::class . ':indexAction')
        ->setName('home');

    $app->group('', function (RouteCollectorProxy $group) {

        $group->get('/dashboard', Controller\Frontend\DashboardController::class . ':indexAction')
            ->setName('dashboard');

        $group->get('/logout', Controller\Frontend\Account\LogoutAction::class)
            ->setName('account:logout');

        $group->get('/endsession', Controller\Frontend\Account\EndMasqueradeAction::class)
            ->setName('account:endmasquerade');

        $group->get('/profile', Controller\Frontend\Profile\IndexAction::class)
            ->setName('profile:index');

        $group->map(['GET', 'POST'], '/profile/edit', Controller\Frontend\Profile\EditAction::class)
            ->setName('profile:edit');

        $group->map(['GET', 'POST'], '/profile/2fa/enable',
            Controller\Frontend\Profile\EnableTwoFactorAction::class)
            ->setName('profile:2fa:enable');

        $group->map(['GET', 'POST'], '/profile/2fa/disable',
            Controller\Frontend\Profile\DisableTwoFactorAction::class)
            ->setName('profile:2fa:disable');

        $group->get('/profile/theme', Controller\Frontend\Profile\ThemeAction::class)
            ->setName('profile:theme');

        $group->get('/api_keys', Controller\Frontend\ApiKeysController::class . ':indexAction')
            ->setName('api_keys:index');

        $group->map(['GET', 'POST'], '/api_keys/edit/{id}',
            Controller\Frontend\ApiKeysController::class . ':editAction')
            ->setName('api_keys:edit');

        $group->map(['GET', 'POST'], '/api_keys/add', Controller\Frontend\ApiKeysController::class . ':editAction')
            ->setName('api_keys:add');

        $group->get('/api_keys/delete/{id}/{csrf}', Controller\Frontend\ApiKeysController::class . ':deleteAction')
            ->setName('api_keys:delete');
    })
        ->add(AzuraMiddleware\EnableView::class)
        ->add(Middleware\RequireLogin::class);

    $app->map(['GET', 'POST'], '/login', Controller\Frontend\Account\LoginAction::class)
        ->setName('account:login')
        ->add(AzuraMiddleware\EnableView::class);

    $app->map(['GET', 'POST'], '/login/2fa', Controller\Frontend\Account\TwoFactorAction::class)
        ->setName('account:login:2fa')
        ->add(AzuraMiddleware\EnableView::class);

    $app->group('/setup', function (RouteCollectorProxy $group) {

        $group->map(['GET', 'POST'], '', Controller\Frontend\SetupController::class . ':indexAction')
            ->setName('setup:index');

        $group->map(['GET', 'POST'], '/complete', Controller\Frontend\SetupController::class . ':completeAction')
            ->setName('setup:complete');

        $group->map(['GET', 'POST'], '/register', Controller\Frontend\SetupController::class . ':registerAction')
            ->setName('setup:register');

        $group->map(['GET', 'POST'], '/station', Controller\Frontend\SetupController::class . ':stationAction')
            ->setName('setup:station');

        $group->map(['GET', 'POST'], '/settings', Controller\Frontend\SetupController::class . ':settingsAction')
            ->setName('setup:settings');

    })
        ->add(AzuraMiddleware\EnableView::class);

    $app->group('/public/{station_id}', function (RouteCollectorProxy $group) {

        $group->get('', Controller\Frontend\PublicController::class . ':indexAction')
            ->setName('public:index');

        $group->get('/embed', Controller\Frontend\PublicController::class . ':embedAction')
            ->setName('public:embed');

        $group->get('/embed-requests', Controller\Frontend\PublicController::class . ':embedrequestsAction')
            ->setName('public:embedrequests');

        $group->get('/playlist[/{format}]', Controller\Frontend\PublicController::class . ':playlistAction')
            ->setName('public:playlist');

        $group->get('/dj', Controller\Frontend\PublicController::class . ':djAction')
            ->setName('public:dj');

    })
        ->add(Middleware\GetStation::class)
        ->add(AzuraMiddleware\EnableView::class);

    $app->group('/station/{station_id}', function (RouteCollectorProxy $group) {

        $group->get('', function (ServerRequest $request, Response $response) {
            return $response->withRedirect((string)$request->getRouter()->fromHere('stations:profile:index'));
        })->setName('stations:index:index');

        $group->group('/automation', function (RouteCollectorProxy $group) {

            $group->map(['GET', 'POST'], '', Controller\Stations\AutomationController::class . ':indexAction')
                ->setName('stations:automation:index');

            $group->get('/run', Controller\Stations\AutomationController::class . ':runAction')
                ->setName('stations:automation:run');

        })->add(new Middleware\Permissions(Acl::STATION_AUTOMATION, true));

        $group->get('/files', Controller\Stations\FilesController::class)
            ->setName('stations:files:index')
            ->add(Middleware\Module\StationFiles::class)
            ->add(new Middleware\Permissions(Acl::STATION_MEDIA, true));

        $group->group('/logs', function (RouteCollectorProxy $group) {

            $group->get('', Controller\Stations\LogsController::class)
                ->setName('stations:logs:index');

            $group->get('/view/{log}', Controller\Stations\LogsController::class . ':viewAction')
                ->setName('stations:logs:view');

        })->add(new Middleware\Permissions(Acl::STATION_LOGS, true));

        $group->get('/playlists', Controller\Stations\PlaylistsController::class)
            ->setName('stations:playlists:index')
            ->add(new Middleware\Permissions(Acl::STATION_MEDIA, true));

        $group->group('/mounts', function (RouteCollectorProxy $group) {

            $group->get('', Controller\Stations\MountsController::class . ':indexAction')
                ->setName('stations:mounts:index');

            $group->map(['GET', 'POST'], '/edit/{id}', Controller\Stations\MountsController::class . ':editAction')
                ->setName('stations:mounts:edit');

            $group->map(['GET', 'POST'], '/add', Controller\Stations\MountsController::class . ':editAction')
                ->setName('stations:mounts:add');

            $group->get('/delete/{id}/{csrf}', Controller\Stations\MountsController::class . ':deleteAction')
                ->setName('stations:mounts:delete');

        })->add(new Middleware\Permissions(Acl::STATION_MOUNTS, true));

        $group->get('/profile', Controller\Stations\ProfileController::class)
            ->setName('stations:profile:index');

        $group->get('/profile/toggle/{feature}/{csrf}', Controller\Stations\ProfileController::class . ':toggleAction')
            ->setName('stations:profile:toggle')
            ->add(new Middleware\Permissions(Acl::STATION_PROFILE, true));

        $group->map(['GET', 'POST'], '/profile/edit', Controller\Stations\ProfileController::class . ':editAction')
            ->setName('stations:profile:edit')
            ->add(new Middleware\Permissions(Acl::STATION_PROFILE, true));

        $group->get('/queue', Controller\Stations\QueueController::class)
            ->setName('stations:queue:index');

        $group->group('/remotes', function (RouteCollectorProxy $group) {

            $group->get('', Controller\Stations\RemotesController::class . ':indexAction')
                ->setName('stations:remotes:index');

            $group->map(['GET', 'POST'], '/edit/{id}', Controller\Stations\RemotesController::class . ':editAction')
                ->setName('stations:remotes:edit');

            $group->map(['GET', 'POST'], '/add', Controller\Stations\RemotesController::class . ':editAction')
                ->setName('stations:remotes:add');

            $group->get('/delete/{id}/{csrf}', Controller\Stations\RemotesController::class . ':deleteAction')
                ->setName('stations:remotes:delete');

        })->add(new Middleware\Permissions(Acl::STATION_REMOTES, true));

        $group->group('/reports', function (RouteCollectorProxy $group) {

            $group->get('/overview', Controller\Stations\Reports\OverviewController::class)
                ->setName('stations:reports:overview');

            $group->get('/timeline[/format/{format}]', Controller\Stations\Reports\TimelineController::class)
                ->setName('stations:reports:timeline');

            $group->get('/performance[/format/{format}]', Controller\Stations\Reports\PerformanceController::class)
                ->setName('stations:reports:performance');

            $group->get('/duplicates', Controller\Stations\Reports\DuplicatesController::class)
                ->setName('stations:reports:duplicates');

            $group->get('/duplicates/delete/{media_id}',
                Controller\Stations\Reports\DuplicatesController::class . ':deleteAction')
                ->setName('stations:reports:duplicates:delete');

            $group->map(['GET', 'POST'], '/listeners', Controller\Stations\Reports\ListenersController::class)
                ->setName('stations:reports:listeners');

            $group->map(['GET', 'POST'], '/soundexchange', Controller\Stations\Reports\SoundExchangeController::class)
                ->setName('stations:reports:soundexchange');

            $group->get('/requests', Controller\Stations\Reports\RequestsController::class)
                ->setName('stations:reports:requests');

            $group->get('/requests/delete/{request_id}/{csrf}',
                Controller\Stations\Reports\RequestsController::class . ':deleteAction')
                ->setName('stations:reports:requests:delete');

        })->add(new Middleware\Permissions(Acl::STATION_REPORTS, true));

        $group->group('/sftp_users', function (RouteCollectorProxy $group) {

            $group->get('', Controller\Stations\SFTPUsersController::class . ':indexAction')
                ->setName('stations:sftp_users:index');

            $group->map(['GET', 'POST'], '/edit/{id}', Controller\Stations\SFTPUsersController::class . ':editAction')
                ->setName('stations:sftp_users:edit');

            $group->map(['GET', 'POST'], '/add', Controller\Stations\SFTPUsersController::class . ':editAction')
                ->setName('stations:sftp_users:add');

            $group->get('/delete/{id}/{csrf}', Controller\Stations\SFTPUsersController::class . ':deleteAction')
                ->setName('stations:sftp_users:delete');

        })->add(new Middleware\Permissions(Acl::STATION_MEDIA, true));

        $group->group('/streamers', function (RouteCollectorProxy $group) {

            $group->get('', Controller\Stations\StreamersController::class . ':indexAction')
                ->setName('stations:streamers:index');

            $group->map(['GET', 'POST'], '/edit/{id}', Controller\Stations\StreamersController::class . ':editAction')
                ->setName('stations:streamers:edit');

            $group->map(['GET', 'POST'], '/add', Controller\Stations\StreamersController::class . ':editAction')
                ->setName('stations:streamers:add');

            $group->get('/delete/{id}/{csrf}', Controller\Stations\StreamersController::class . ':deleteAction')
                ->setName('stations:streamers:delete');

        })->add(new Middleware\Permissions(Acl::STATION_STREAMERS, true));

        $group->group('/webhooks', function (RouteCollectorProxy $group) {

            $group->get('', Controller\Stations\WebhooksController::class . ':indexAction')
                ->setName('stations:webhooks:index');

            $group->map(['GET', 'POST'], '/edit/{id}', Controller\Stations\WebhooksController::class . ':editAction')
                ->setName('stations:webhooks:edit');

            $group->map(['GET', 'POST'], '/add[/{type}]', Controller\Stations\WebhooksController::class . ':addAction')
                ->setName('stations:webhooks:add');

            $group->get('/toggle/{id}/{csrf}', Controller\Stations\WebhooksController::class . ':toggleAction')
                ->setName('stations:webhooks:toggle');

            $group->get('/test/{id}/{csrf}', Controller\Stations\WebhooksController::class . ':testAction')
                ->setName('stations:webhooks:test');

            $group->get('/delete/{id}/{csrf}', Controller\Stations\WebhooksController::class . ':deleteAction')
                ->setName('stations:webhooks:delete');

        })->add(new Middleware\Permissions(Acl::STATION_WEB_HOOKS, true));

        // END /stations GROUP

    })
        ->add(Middleware\Module\Stations::class)
        ->add(new Middleware\Permissions(Acl::STATION_VIEW, true))
        ->add(Middleware\RequireStation::class)
        ->add(Middleware\GetStation::class)
        ->add(AzuraMiddleware\EnableView::class)
        ->add(Middleware\RequireLogin::class);

};
