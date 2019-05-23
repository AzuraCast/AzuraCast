<?php
use App\Controller;
use App\Middleware;
use App\Acl;
use Azura\App;
use Azura\Middleware as AzuraMiddleware;

return function(App $app)
{
    $app->group('/admin', function () {
        /** @var App $this */

        $this->get('', Controller\Admin\IndexController::class.':indexAction')
            ->setName('admin:index:index');

        $this->get('/sync/{type}', Controller\Admin\IndexController::class.':syncAction')
            ->setName('admin:index:sync')
            ->add([Middleware\Permissions::class, Acl::GLOBAL_ALL]);

        $this->group('/install', function () {
            /** @var App $this */

            $this->map(['GET', 'POST'], '/shoutcast', Controller\Admin\InstallShoutcastController::class)
                ->setName('admin:install:shoutcast');

        })->add([Middleware\Permissions::class, Acl::GLOBAL_ALL]);

        $this->group('/api', function () {
            /** @var App $this */

            $this->get('', Controller\Admin\ApiController::class.':indexAction')
                ->setName('admin:api:index');

            $this->map(['GET', 'POST'], '/edit/{id}', Controller\Admin\ApiController::class.':editAction')
                ->setName('admin:api:edit');

            $this->get('/delete/{id}/{csrf}', Controller\Admin\ApiController::class.':deleteAction')
                ->setName('admin:api:delete');

        })->add([Middleware\Permissions::class, Acl::GLOBAL_API_KEYS]);

        $this->group('/backups', function() {
            /** @var App $this */

            $this->get('', Controller\Admin\BackupsController::class)
                ->setName('admin:backups:index');

            $this->map(['GET', 'POST'], '/configure', Controller\Admin\BackupsController::class.':configureAction')
                ->setName('admin:backups:configure');

            $this->map(['GET', 'POST'], '/run', Controller\Admin\BackupsController::class.':runAction')
                ->setName('admin:backups:run');

            $this->get('/delete/{path}', Controller\Admin\BackupsController::class.':downloadAction')
                ->setName('admin:backups:download');

            $this->get('/delete/{path}/{csrf}', Controller\Admin\BackupsController::class.':deleteAction')
                ->setName('admin:backups:delete');

        })->add([Middleware\Permissions::class, Acl::GLOBAL_BACKUPS]);

        $this->map(['GET', 'POST'], '/branding', Controller\Admin\BrandingController::class.':indexAction')
            ->setName('admin:branding:index')
            ->add([Middleware\Permissions::class, Acl::GLOBAL_SETTINGS]);

        $this->group('/custom_fields', function() {
            /** @var App $this */

            $this->get('', Controller\Admin\CustomFieldsController::class.':indexAction')
                ->setName('admin:custom_fields:index');

            $this->map(['GET', 'POST'], '/edit/{id}', Controller\Admin\CustomFieldsController::class.':editAction')
                ->setName('admin:custom_fields:edit');

            $this->map(['GET', 'POST'], '/add', Controller\Admin\CustomFieldsController::class.':editAction')
                ->setName('admin:custom_fields:add');

            $this->get('/delete/{id}/{csrf}', Controller\Admin\CustomFieldsController::class.':deleteAction')
                ->setName('admin:custom_fields:delete');

        })->add([Middleware\Permissions::class, Acl::GLOBAL_CUSTOM_FIELDS]);

        $this->group('/logs', function () {
            /** @var App $this */

            $this->get('', Controller\Admin\LogsController::class)
                ->setName('admin:logs:index');

            $this->get('/view/{station}/{log}', Controller\Admin\LogsController::class.':viewAction')
                ->setName('admin:logs:view')
                ->add([Middleware\GetStation::class, false]);

        })
            ->add([Middleware\Permissions::class, Acl::GLOBAL_LOGS]);

        $this->group('/permissions', function () {
            /** @var App $this */

            $this->get('', Controller\Admin\PermissionsController::class.':indexAction')
                ->setName('admin:permissions:index');

            $this->map(['GET', 'POST'], '/edit/{id}', Controller\Admin\PermissionsController::class.':editAction')
                ->setName('admin:permissions:edit');

            $this->map(['GET', 'POST'], '/add', Controller\Admin\PermissionsController::class.':editAction')
                ->setName('admin:permissions:add');

            $this->get('/delete/{id}/{csrf}', Controller\Admin\PermissionsController::class.':deleteAction')
                ->setName('admin:permissions:delete');

        })->add([Middleware\Permissions::class, Acl::GLOBAL_PERMISSIONS]);

        $this->map(['GET', 'POST'], '/settings', Controller\Admin\SettingsController::class.':indexAction')
            ->setName('admin:settings:index')
            ->add([Middleware\Permissions::class, Acl::GLOBAL_SETTINGS]);

        $this->group('/stations', function () {
            /** @var App $this */

            $this->get('', Controller\Admin\StationsController::class)
                ->setName('admin:stations:index');

            $this->map(['GET', 'POST'], '/edit/{id}', Controller\Admin\StationsController::class.':editAction')
                ->setName('admin:stations:edit');

            $this->map(['GET', 'POST'], '/add', Controller\Admin\StationsController::class.':editAction')
                ->setName('admin:stations:add');

            $this->map(['GET', 'POST'], '/clone/{id}', Controller\Admin\StationsController::class.':cloneAction')
                ->setName('admin:stations:clone');

            $this->get('/delete/{id}/{csrf}', Controller\Admin\StationsController::class.':deleteAction')
                ->setName('admin:stations:delete');

        })->add([Middleware\Permissions::class, Acl::GLOBAL_STATIONS]);

        $this->group('/users', function () {
            /** @var App $this */

            $this->get('', Controller\Admin\UsersController::class.':indexAction')
                ->setName('admin:users:index');

            $this->map(['GET', 'POST'], '/edit/{id}', Controller\Admin\UsersController::class.':editAction')
                ->setName('admin:users:edit');

            $this->map(['GET', 'POST'], '/add', Controller\Admin\UsersController::class.':editAction')
                ->setName('admin:users:add');

            $this->get('/delete/{id}/{csrf}', Controller\Admin\UsersController::class.':deleteAction')
                ->setName('admin:users:delete');

            $this->get('/login-as/{id}/{csrf}', Controller\Admin\UsersController::class.':impersonateAction')
                ->setName('admin:users:impersonate');

        })->add([Middleware\Permissions::class, Acl::GLOBAL_USERS]);

        // END /admin GROUP

    })
        ->add(Middleware\Module\Admin::class)
        ->add(AzuraMiddleware\EnableView::class)
        ->add([Middleware\Permissions::class, Acl::GLOBAL_VIEW])
        ->add(Middleware\RequireLogin::class);

    $app->group('/api', function () {
        /** @var App $this */

        $this->options('/{routes:.+}', function (\App\Http\Request $request, \App\Http\Response $response) {
            return $response
                ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                ->withHeader('Access-Control-Allow-Headers', 'x-requested-with, Content-Type, Accept, Origin, Authorization')
                ->withHeader('Access-Control-Allow-Origin', '*');
        });

        $this->get('', Controller\Api\IndexController::class.':indexAction')
            ->setName('api:index:index');

        $this->get('/openapi.yml', Controller\Api\OpenApiController::class)
            ->setName('api:openapi');

        $this->get('/status', Controller\Api\IndexController::class.':statusAction')
            ->setName('api:index:status');

        $this->get('/time', Controller\Api\IndexController::class.':timeAction')
            ->setName('api:index:time');

        $this->group('/internal', function () {
            /** @var App $this */

            $this->group('/{station}', function() {
                /** @var App $this */

                // Liquidsoap internal authentication functions
                $this->map(['GET', 'POST'], '/auth', Controller\Api\InternalController::class.':authAction')
                    ->setName('api:internal:auth');

                $this->map(['GET', 'POST'], '/nextsong', Controller\Api\InternalController::class.':nextsongAction')
                    ->setName('api:internal:nextsong');

                $this->map(['GET', 'POST'], '/djon', Controller\Api\InternalController::class.':djonAction')
                    ->setName('api:internal:djon');

                $this->map(['GET', 'POST'], '/djoff', Controller\Api\InternalController::class.':djoffAction')
                    ->setName('api:internal:djoff');

                $this->map(['GET', 'POST'], '/feedback', Controller\Api\InternalController::class.':feedbackAction')
                    ->setName('api:internal:feedback');

            })->add(Middleware\GetStation::class);

        });

        $this->get('/nowplaying[/{station}]', Controller\Api\NowplayingController::class)
            ->setName('api:nowplaying:index');

        $this->get('/stations', Controller\Api\Stations\IndexController::class.':listAction')
            ->setName('api:stations:list')
            ->add([AzuraMiddleware\RateLimit::class, 'api', 5, 2]);

        $this->group('/admin', function() {
            /** @var App $this */

            $this->get('/permissions', Controller\Api\Admin\PermissionsController::class)
                ->add([Middleware\Permissions::class, Acl::GLOBAL_PERMISSIONS]);

            $this->group('', function() {
                /** @var App $this */
                $this->get('/settings', Controller\Api\Admin\SettingsController::class.':listAction')
                    ->setName('api:admin:settings');

                $this->put('/settings', Controller\Api\Admin\SettingsController::class.':updateAction');
            })->add([Middleware\Permissions::class, Acl::GLOBAL_SETTINGS]);

            $admin_api_endpoints = [
                ['custom_field', 'custom_fields', Controller\Api\Admin\CustomFieldsController::class, Acl::GLOBAL_CUSTOM_FIELDS],
                ['role', 'roles', Controller\Api\Admin\RolesController::class, Acl::GLOBAL_PERMISSIONS],
                ['station', 'stations', Controller\Api\Admin\StationsController::class, Acl::GLOBAL_STATIONS],
                ['user', 'users', Controller\Api\Admin\UsersController::class, Acl::GLOBAL_USERS],
            ];

            foreach($admin_api_endpoints as [$singular, $plural, $class, $permission]) {
                $this->group('', function() use ($singular, $plural, $class) {
                    /** @var App $this */
                    $this->get('/'.$plural, $class.':listAction')
                        ->setName('api:admin:'.$plural);
                    $this->post('/'.$plural, $class.':createAction');

                    $this->get('/'.$singular.'/{id}', $class.':getAction')
                        ->setName('api:admin:'.$singular);
                    $this->put('/'.$singular.'/{id}', $class.':editAction');
                    $this->delete('/'.$singular.'/{id}', $class.':deleteAction');
                })->add([Middleware\Permissions::class, $permission]);
            }
        });

        $this->group('/station/{station}', function () {
            /** @var App $this */

            $this->get('', Controller\Api\Stations\IndexController::class.':indexAction')
                ->setName('api:stations:index')
                ->add([AzuraMiddleware\RateLimit::class, 'api', 5, 2]);

            $this->get('/nowplaying', Controller\Api\NowplayingController::class.':indexAction');

            $this->get('/history', Controller\Api\Stations\HistoryController::class)
                ->setName('api:stations:history')
                ->add([Middleware\Permissions::class, Acl::STATION_REPORTS, true]);

            $this->group('/queue', function() {
                /** @var App $this */
                $this->get('', Controller\Api\Stations\QueueController::class.':listAction')
                    ->setName('api:stations:queue');

                $this->get('/{id}', Controller\Api\Stations\QueueController::class.':getAction')
                    ->setName('api:stations:queue:record');

                $this->delete('/{id}', Controller\Api\Stations\QueueController::class.':deleteAction');
            })->add([Middleware\Permissions::class, Acl::STATION_BROADCASTING, true]);

            $this->get('/requests', Controller\Api\Stations\RequestsController::class.':listAction')
                ->setName('api:requests:list');

            $this->map(['GET', 'POST'], '/request/{media_id}', Controller\Api\Stations\RequestsController::class.':submitAction')
                ->setName('api:requests:submit')
                ->add([AzuraMiddleware\RateLimit::class, 'api', 5, 2]);

            $this->get('/listeners', Controller\Api\Stations\ListenersController::class.':indexAction')
                ->setName('api:listeners:index')
                ->add([Middleware\Permissions::class, Acl::STATION_REPORTS, true]);

            $this->get('/art/{media_id:[a-zA-Z0-9]+}.jpg', Controller\Api\Stations\ArtController::class)
                ->setName('api:stations:media:art');

            $this->get('/art/{media_id:[a-zA-Z0-9]+}', Controller\Api\Stations\ArtController::class);

            $station_api_endpoints = [
                ['file', 'files', Controller\Api\Stations\FilesController::class, Acl::STATION_MEDIA],
                ['mount', 'mounts', Controller\Api\Stations\MountsController::class, Acl::STATION_MOUNTS],
                ['playlist', 'playlists', Controller\Api\Stations\PlaylistsController::class, Acl::STATION_MEDIA],
                ['remote', 'remotes', Controller\Api\Stations\RemotesController::class, Acl::STATION_REMOTES],
                ['streamer', 'streamers', Controller\Api\Stations\StreamersController::class, Acl::STATION_STREAMERS],
                ['webhook', 'webhooks', Controller\Api\Stations\WebhooksController::class, Acl::STATION_WEB_HOOKS],
            ];

            foreach($station_api_endpoints as [$singular, $plural, $class, $permission]) {
                $this->group('', function() use ($singular, $plural, $class) {
                    /** @var App $this */
                    $this->get('/'.$plural, $class.':listAction')
                        ->setName('api:stations:'.$plural);
                    $this->post('/'.$plural, $class.':createAction');

                    $this->get('/'.$singular.'/{id}', $class.':getAction')
                        ->setName('api:stations:'.$singular);
                    $this->put('/'.$singular.'/{id}', $class.':editAction');
                    $this->delete('/'.$singular.'/{id}', $class.':deleteAction');
                })->add([Middleware\Permissions::class, $permission, true]);
            }

            $this->get('/status', Controller\Api\Stations\ServicesController::class.':statusAction')
                ->setName('api:stations:status')
                ->add([Middleware\Permissions::class, Acl::STATION_VIEW, true]);

            $this->post('/backend/{do}', Controller\Api\Stations\ServicesController::class.':backendAction')
                ->setName('api:stations:backend')
                ->add([Middleware\Permissions::class, Acl::STATION_BROADCASTING, true]);

            $this->post('/frontend/{do}', Controller\Api\Stations\ServicesController::class.':frontendAction')
                ->setName('api:stations:frontend')
                ->add([Middleware\Permissions::class, Acl::STATION_BROADCASTING, true]);

            $this->post('/restart', Controller\Api\Stations\ServicesController::class.':restartAction')
                ->setName('api:stations:restart')
                ->add([Middleware\Permissions::class, Acl::STATION_BROADCASTING, true]);

        })->add(Middleware\GetStation::class);

        // END /api GROUP

    })
        ->add(Middleware\Module\Api::class);

    $app->get('/', Controller\Frontend\IndexController::class.':indexAction')
        ->setName('home');

    $app->group('', function() {
        /** @var App $this */

        $this->get('/dashboard', Controller\Frontend\DashboardController::class.':indexAction')
            ->setName('dashboard');

        $this->get('/logout', Controller\Frontend\AccountController::class.':logoutAction')
            ->setName('account:logout');

        $this->get('/endsession', Controller\Frontend\AccountController::class.':endmasqueradeAction')
            ->setName('account:endmasquerade');

        $this->get('/profile', Controller\Frontend\ProfileController::class.':indexAction')
            ->setName('profile:index');

        $this->map(['GET', 'POST'], '/profile/edit', Controller\Frontend\ProfileController::class.':editAction')
            ->setName('profile:edit');

        $this->map(['GET', 'POST'], '/profile/2fa/enable', Controller\Frontend\ProfileController::class.':enableTwoFactorAction')
            ->setName('profile:2fa:enable');

        $this->map(['GET', 'POST'], '/profile/2fa/disable', Controller\Frontend\ProfileController::class.':disableTwoFactorAction')
            ->setName('profile:2fa:disable');

        $this->get('/profile/theme', Controller\Frontend\ProfileController::class.':themeAction')
            ->setName('profile:theme');

        $this->get('/api_keys', Controller\Frontend\ApiKeysController::class.':indexAction')
            ->setName('api_keys:index');

        $this->map(['GET', 'POST'], '/api_keys/edit/{id}', Controller\Frontend\ApiKeysController::class.':editAction')
            ->setName('api_keys:edit');

        $this->map(['GET', 'POST'], '/api_keys/add', Controller\Frontend\ApiKeysController::class.':editAction')
            ->setName('api_keys:add');

        $this->get('/api_keys/delete/{id}/{csrf}', Controller\Frontend\ApiKeysController::class.':deleteAction')
            ->setName('api_keys:delete');
    })
        ->add(AzuraMiddleware\EnableView::class)
        ->add(Middleware\RequireLogin::class);

    $app->map(['GET', 'POST'], '/login', Controller\Frontend\AccountController::class.':loginAction')
        ->setName('account:login')
        ->add(AzuraMiddleware\EnableView::class);

    $app->map(['GET', 'POST'], '/login/2fa', Controller\Frontend\AccountController::class.':twoFactorAction')
        ->setName('account:login:2fa')
        ->add(AzuraMiddleware\EnableView::class);

    $app->group('/setup', function () {
        /** @var App $this */

        $this->map(['GET', 'POST'], '', Controller\Frontend\SetupController::class.':indexAction')
            ->setName('setup:index');

        $this->map(['GET', 'POST'], '/complete', Controller\Frontend\SetupController::class.':completeAction')
            ->setName('setup:complete');

        $this->map(['GET', 'POST'], '/register', Controller\Frontend\SetupController::class.':registerAction')
            ->setName('setup:register');

        $this->map(['GET', 'POST'], '/station', Controller\Frontend\SetupController::class.':stationAction')
            ->setName('setup:station');

        $this->map(['GET', 'POST'], '/settings', Controller\Frontend\SetupController::class.':settingsAction')
            ->setName('setup:settings');

    })
        ->add(AzuraMiddleware\EnableView::class);

    $app->group('/public/{station}', function () {
        /** @var App $this */

        $this->get('', Controller\Frontend\PublicController::class.':indexAction')
            ->setName('public:index');

        $this->get('/embed', Controller\Frontend\PublicController::class.':embedAction')
            ->setName('public:embed');

        $this->get('/embed-requests', Controller\Frontend\PublicController::class.':embedrequestsAction')
            ->setName('public:embedrequests');

        $this->get('/playlist[/{format}]', Controller\Frontend\PublicController::class.':playlistAction')
            ->setName('public:playlist');

        $this->get('/dj', Controller\Frontend\PublicController::class.':djAction')
            ->setName('public:dj');

    })
        ->add(Middleware\GetStation::class)
        ->add(AzuraMiddleware\EnableView::class);

    $app->group('/station/{station}', function () {
        /** @var App $this */

        $this->get('', function (\App\Http\Request $request, \App\Http\Response $response) {
            return $response->withRedirect($request->getRouter()->fromHere('stations:profile:index'));
        })->setName('stations:index:index');

        $this->group('/automation', function () {
            /** @var App $this */

            $this->map(['GET', 'POST'], '', Controller\Stations\AutomationController::class.':indexAction')
                ->setName('stations:automation:index');

            $this->get('/run', Controller\Stations\AutomationController::class.':runAction')
                ->setName('stations:automation:run');

        })->add([Middleware\Permissions::class, Acl::STATION_AUTOMATION, true]);

        $this->group('/files', function () {
            /** @var App $this */

            $this->get('', Controller\Stations\Files\FilesController::class)
                ->setName('stations:files:index');

            $this->map(['GET', 'POST'], '/edit/{id}', Controller\Stations\Files\EditController::class)
                ->setName('stations:files:edit');

            $this->map(['GET', 'POST'], '/rename', Controller\Stations\Files\FilesController::class.':renameAction')
                ->setName('stations:files:rename');

            $this->map(['GET', 'POST'], '/list', Controller\Stations\Files\ListController::class)
                ->setName('stations:files:list');

            $this->map(['GET', 'POST'], '/directories', Controller\Stations\Files\FilesController::class.':listDirectoriesAction')
                ->setName('stations:files:directories');

            $this->map(['GET', 'POST'], '/batch', Controller\Stations\Files\BatchController::class)
                ->setName('stations:files:batch');

            $this->map(['GET', 'POST'], '/mkdir', Controller\Stations\Files\FilesController::class.':mkdirAction')
                ->setName('stations:files:mkdir');

            $this->map(['GET', 'POST'], '/upload', Controller\Stations\Files\FilesController::class.':uploadAction')
                ->setName('stations:files:upload');

            $this->map(['GET', 'POST'], '/download', Controller\Stations\Files\FilesController::class.':downloadAction')
                ->setName('stations:files:download');

        })
            ->add(Middleware\Module\StationFiles::class)
            ->add([Middleware\Permissions::class, Acl::STATION_MEDIA, true]);

        $this->group('/logs', function () {
            /** @var App $this */

            $this->get('', Controller\Stations\LogsController::class)
                ->setName('stations:logs:index');

            $this->get('/view/{log}', Controller\Stations\LogsController::class.':viewAction')
                ->setName('stations:logs:view');

        })
            ->add([Middleware\Permissions::class, Acl::STATION_LOGS, true]);

        $this->group('/playlists', function () {
            /** @var App $this */

            $this->get('', Controller\Stations\PlaylistsController::class.':indexAction')
                ->setName('stations:playlists:index');

            $this->get('/schedule', Controller\Stations\PlaylistsController::class.':scheduleAction')
                ->setName('stations:playlists:schedule');

            $this->map(['GET', 'POST'], '/edit/{id}', Controller\Stations\PlaylistsController::class.':editAction')
                ->setName('stations:playlists:edit');

            $this->map(['GET', 'POST'], '/add', Controller\Stations\PlaylistsController::class.':editAction')
                ->setName('stations:playlists:add');

            $this->get('/delete/{id}/{csrf}', Controller\Stations\PlaylistsController::class.':deleteAction')
                ->setName('stations:playlists:delete');

            $this->map(['GET', 'POST'], '/reorder/{id}', Controller\Stations\PlaylistsController::class.':reorderAction')
                ->setName('stations:playlists:reorder');

            $this->get('/toggle/{id}', Controller\Stations\PlaylistsController::class.':toggleAction')
                ->setName('stations:playlists:toggle');

            $this->get('/export/{id}[/{format}]', Controller\Stations\PlaylistsController::class.':exportAction')
                ->setName('stations:playlists:export');

        })->add([Middleware\Permissions::class, Acl::STATION_MEDIA, true]);

        $this->group('/mounts', function () {
            /** @var App $this */

            $this->get('', Controller\Stations\MountsController::class.':indexAction')
                ->setName('stations:mounts:index');

            $this->map(['GET', 'POST'], '/edit/{id}', Controller\Stations\MountsController::class.':editAction')
                ->setName('stations:mounts:edit');

            $this->map(['GET', 'POST'], '/add', Controller\Stations\MountsController::class.':editAction')
                ->setName('stations:mounts:add');

            $this->get('/delete/{id}/{csrf}', Controller\Stations\MountsController::class.':deleteAction')
                ->setName('stations:mounts:delete');

        })->add([Middleware\Permissions::class, Acl::STATION_MOUNTS, true]);

        $this->get('/profile', Controller\Stations\ProfileController::class)
            ->setName('stations:profile:index');

        $this->get('/profile/toggle/{feature}/{csrf}', Controller\Stations\ProfileController::class.':toggleAction')
            ->setName('stations:profile:toggle')
            ->add([Middleware\Permissions::class, Acl::STATION_PROFILE, true]);

        $this->map(['GET', 'POST'], '/profile/edit', Controller\Stations\ProfileController::class.':editAction')
            ->setName('stations:profile:edit')
            ->add([Middleware\Permissions::class, Acl::STATION_PROFILE, true]);

        $this->get('/queue', Controller\Stations\QueueController::class)
            ->setName('stations:queue:index');

        $this->group('/remotes', function () {
            /** @var App $this */

            $this->get('', Controller\Stations\RemotesController::class.':indexAction')
                ->setName('stations:remotes:index');

            $this->map(['GET', 'POST'], '/edit/{id}', Controller\Stations\RemotesController::class.':editAction')
                ->setName('stations:remotes:edit');

            $this->map(['GET', 'POST'], '/add', Controller\Stations\RemotesController::class.':editAction')
                ->setName('stations:remotes:add');

            $this->get('/delete/{id}/{csrf}', Controller\Stations\RemotesController::class.':deleteAction')
                ->setName('stations:remotes:delete');

        })->add([Middleware\Permissions::class, Acl::STATION_REMOTES, true]);

        $this->group('/reports', function () {
            /** @var App $this */

            $this->get('/overview', Controller\Stations\Reports\OverviewController::class)
                ->setName('stations:reports:overview');

            $this->get('/timeline[/format/{format}]', Controller\Stations\Reports\TimelineController::class)
                ->setName('stations:reports:timeline');

            $this->get('/performance[/format/{format}]', Controller\Stations\Reports\PerformanceController::class)
                ->setName('stations:reports:performance');

            $this->get('/duplicates', Controller\Stations\Reports\DuplicatesController::class)
                ->setName('stations:reports:duplicates');

            $this->get('/duplicates/delete/{media_id}', Controller\Stations\Reports\DuplicatesController::class.':deleteAction')
                ->setName('stations:reports:duplicates:delete');

            $this->map(['GET', 'POST'], '/listeners', Controller\Stations\Reports\ListenersController::class)
                ->setName('stations:reports:listeners');

            $this->map(['GET', 'POST'], '/soundexchange', Controller\Stations\Reports\SoundExchangeController::class)
                ->setName('stations:reports:soundexchange');

            $this->get('/requests', Controller\Stations\Reports\RequestsController::class)
                ->setName('stations:reports:requests');

            $this->get('/requests/delete/{request_id}/{csrf}', Controller\Stations\Reports\RequestsController::class.':deleteAction')
                ->setName('stations:reports:requests:delete');

        })->add([Middleware\Permissions::class, Acl::STATION_REPORTS, true]);

        $this->group('/streamers', function () {
            /** @var App $this */

            $this->get('', Controller\Stations\StreamersController::class.':indexAction')
                ->setName('stations:streamers:index');

            $this->map(['GET', 'POST'], '/edit/{id}', Controller\Stations\StreamersController::class.':editAction')
                ->setName('stations:streamers:edit');

            $this->map(['GET', 'POST'], '/add', Controller\Stations\StreamersController::class.':editAction')
                ->setName('stations:streamers:add');

            $this->get('/delete/{id}/{csrf}', Controller\Stations\StreamersController::class.':deleteAction')
                ->setName('stations:streamers:delete');

        })->add([Middleware\Permissions::class, Acl::STATION_STREAMERS, true]);

        $this->group('/webhooks', function () {
            /** @var App $this */

            $this->get('', Controller\Stations\WebhooksController::class.':indexAction')
                ->setName('stations:webhooks:index');

            $this->map(['GET', 'POST'], '/edit/{id}', Controller\Stations\WebhooksController::class.':editAction')
                ->setName('stations:webhooks:edit');

            $this->map(['GET', 'POST'], '/add[/{type}]', Controller\Stations\WebhooksController::class.':addAction')
                ->setName('stations:webhooks:add');

            $this->get('/toggle/{id}/{csrf}', Controller\Stations\WebhooksController::class.':toggleAction')
                ->setName('stations:webhooks:toggle');

            $this->get('/test/{id}/{csrf}', Controller\Stations\WebhooksController::class.':testAction')
                ->setName('stations:webhooks:test');

            $this->get('/delete/{id}/{csrf}', Controller\Stations\WebhooksController::class.':deleteAction')
                ->setName('stations:webhooks:delete');

        })->add([Middleware\Permissions::class, Acl::STATION_WEB_HOOKS, true]);

        // END /stations GROUP

    })
        ->add(Middleware\Module\Stations::class)
        ->add([Middleware\Permissions::class, Acl::STATION_VIEW, true])
        ->add(Middleware\GetStation::class)
        ->add(AzuraMiddleware\EnableView::class)
        ->add(Middleware\RequireLogin::class);

};
