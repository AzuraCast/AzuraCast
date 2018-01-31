<?php
use \Controller;
use \AzuraCast\Middleware;

return function(\Slim\App $app) {

    $app->group('/admin', function () {

        $this->get('', Controller\Admin\IndexController::class.':indexAction')
            ->setName('admin:index:index');

        $this->get('/sync/{type}', Controller\Admin\IndexController::class.':syncAction')
            ->setName('admin:index:sync')
            ->add([Middleware\Permissions::class, 'administer all']);

        $this->group('/api', function () {

            $this->get('', Controller\Admin\ApiController::class.':indexAction')
                ->setName('admin:api:index');

            $this->map(['GET', 'POST'], '/edit[/{id}]', Controller\Admin\ApiController::class.':editAction')
                ->setName('admin:api:edit');

            $this->get('/delete/{id}', Controller\Admin\ApiController::class.':deleteAction')
                ->setName('admin:api:delete');

        })->add([Middleware\Permissions::class, 'administer api keys']);

        $this->map(['GET', 'POST'], '/branding', Controller\Admin\BrandingController::class.':indexAction')
            ->setName('admin:branding:index')
            ->add([Middleware\Permissions::class, 'administer settings']);

        $this->group('/permissions', function () {

            $this->get('', Controller\Admin\PermissionsController::class.':indexAction')
                ->setName('admin:permissions:index');

            $this->map(['GET', 'POST'], '/edit[/{id}]', Controller\Admin\PermissionsController::class.':editAction')
                ->setName('admin:permissions:edit');

            $this->get('/delete/{id}', Controller\Admin\PermissionsController::class.':deleteAction')
                ->setName('admin:permissions:delete');

        })->add([Middleware\Permissions::class, 'administer permissions']);

        $this->map(['GET', 'POST'], '/settings', Controller\Admin\SettingsController::class.':indexAction')
            ->setName('admin:settings:index')
            ->add([Middleware\Permissions::class, 'administer settings']);

        $this->group('/stations', function () {

            $this->get('', Controller\Admin\StationsController::class.':indexAction')
                ->setName('admin:stations:index');

            $this->map(['GET', 'POST'], '/edit[/{id}]', Controller\Admin\StationsController::class.':editAction')
                ->setName('admin:stations:edit');

            $this->map(['GET', 'POST'], '/clone/{id}', Controller\Admin\StationsController::class.':cloneAction')
                ->setName('admin:stations:clone');

            $this->get('/delete/{id}', Controller\Admin\StationsController::class.':deleteAction')
                ->setName('admin:stations:delete');

        })->add([Middleware\Permissions::class, 'administer stations']);

        $this->group('/users', function () {

            $this->get('', Controller\Admin\UsersController::class.':indexAction')
                ->setName('admin:users:index');

            $this->map(['GET', 'POST'], '/edit[/{id}]', Controller\Admin\UsersController::class.':editAction')
                ->setName('admin:users:edit');

            $this->get('/delete/{id}', Controller\Admin\UsersController::class.':deleteAction')
                ->setName('admin:users:delete');

            $this->get('/login-as/{id}', Controller\Admin\UsersController::class.':impersonateAction')
                ->setName('admin:users:impersonate');

        })->add([Middleware\Permissions::class, 'administer users']);

        // END /admin GROUP

    })
        ->add(Middleware\Module\Admin::class)
        ->add(Middleware\EnableView::class)
        ->add([Middleware\Permissions::class, 'view administration'])
        ->add(Middleware\RequireLogin::class);

    $app->group('/api', function () {

        $this->get('', Controller\Api\IndexController::class.':indexAction')
            ->setName('api:index:index');

        $this->get('/status', Controller\Api\IndexController::class.':statusAction')
            ->setName('api:index:status');

        $this->get('/time', Controller\Api\IndexController::class.':timeAction')
            ->setName('api:index:time');

        $this->group('/internal', function () {

            $this->group('/{station}', function() {

                $this->map(['GET', 'POST'], '/auth', Controller\Api\InternalController::class.':authAction')
                    ->setName('api:internal:auth');

                $this->map(['GET', 'POST'], '/nextsong', Controller\Api\InternalController::class.':nextsongAction')
                    ->setName('api:internal:nextsong');

                $this->map(['GET', 'POST'], '/notify', Controller\Api\InternalController::class.':notifyAction')
                    ->setName('api:internal:notify');

            })->add(Middleware\GetStation::class);

        });

        $this->get('/nowplaying[/{station}]', Controller\Api\NowplayingController::class.':indexAction')
            ->setName('api:nowplaying:index');

        $this->get('/stations', Controller\Api\StationsController::class.':listAction')
            ->setName('api:stations:list')
            ->add([Middleware\RateLimit::class, 'api', 5, 2]);

        $this->group('/station/{station}', function () {

            $this->get('', Controller\Api\StationsController::class.':indexAction')
                ->setName('api:stations:index')
                ->add([Middleware\RateLimit::class, 'api', 5, 2]);

            $this->get('/nowplaying', Controller\Api\NowplayingController::class.':indexAction');

            // This would not normally be POST-able, but Bootgrid requires it
            $this->map(['GET', 'POST'], '/requests', Controller\Api\RequestsController::class.':listAction')
                ->setName('api:requests:list')
                ->add([Middleware\RateLimit::class, 'api', 5, 2]);

            $this->map(['GET', 'POST'], '/request/{media_id}', Controller\Api\RequestsController::class.':submitAction')
                ->setName('api:requests:submit')
                ->add([Middleware\RateLimit::class, 'api', 5, 2]);

            $this->get('/listeners', Controller\Api\ListenersController::class.':indexAction')
                ->setName('api:listeners:index')
                ->add([Middleware\Permissions::class, 'view station reports', true]);

            $this->get('/art/{media_id}', Controller\Api\MediaController::class.':artAction')
                ->setName('api:media:art');

        })->add(Middleware\GetStation::class);

        // END /api GROUP

    })
        ->add(Middleware\Module\Api::class);

    $app->group('', function() {

        $this->get('/', Controller\Frontend\IndexController::class.':indexAction')
            ->setName('home');

        $this->get('/logout', Controller\Frontend\AccountController::class.':logoutAction')
            ->setName('account:logout');

        $this->get('/endsession', Controller\Frontend\AccountController::class.':endmasqueradeAction')
            ->setName('account:endmasquerade');

        $this->get('/profile', Controller\Frontend\ProfileController::class.':indexAction')
            ->setName('profile:index');

        $this->map(['GET', 'POST'], '/profile/edit', Controller\Frontend\ProfileController::class.':editAction')
            ->setName('profile:edit');

        // Used for internal development
        if (!APP_IN_PRODUCTION) {
            $this->any('/test', Controller\Frontend\UtilController::class.':testAction')
                ->setName('util:test');
        }

    })
        ->add(Middleware\EnableView::class)
        ->add(Middleware\RequireLogin::class);

    $app->map(['GET', 'POST'], '/login', Controller\Frontend\AccountController::class.':loginAction')
        ->setName('account:login')
        ->add(Middleware\EnableView::class);

    $app->group('/setup', function () {

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
        ->add(Middleware\EnableView::class);

    $app->group('/public/{station}', function () {

        $this->get('', Controller\Frontend\PublicController::class.':indexAction')
            ->setName('public:index');

        $this->get('/embed', Controller\Frontend\PublicController::class.':embedAction')
            ->setName('public:embed');

        $this->get('/embed-requests', Controller\Frontend\PublicController::class.':embedrequestsAction')
            ->setName('public:embedrequests');

        $this->get('/playlist[/{format}]', Controller\Frontend\PublicController::class.':playlistAction')
            ->setName('public:playlist');

    })
        ->add(Middleware\GetStation::class)
        ->add(Middleware\EnableView::class);

    $app->group('/station/{station}', function () {

        $this->get('', Controller\Stations\IndexController::class.':indexAction')
            ->setName('stations:index:index');

        $this->group('/automation', function () {

            $this->map(['GET', 'POST'], '', Controller\Stations\AutomationController::class.':indexAction')
                ->setName('stations:automation:index');

            $this->get('/run', Controller\Stations\AutomationController::class.':runAction')
                ->setName('stations:automation:run');

        })->add([Middleware\Permissions::class, 'manage station automation', true]);

        $this->group('/files', function () {

            $this->get('', Controller\Stations\FilesController::class.':indexAction')
                ->setName('stations:files:index');

            $this->map(['GET', 'POST'], '/edit/{id}', Controller\Stations\FilesController::class.':editAction')
                ->setName('stations:files:edit');

            $this->map(['GET', 'POST'], '/rename/{path}', Controller\Stations\FilesController::class.':renameAction')
                ->setName('stations:files:rename');

            $this->map(['GET', 'POST'], '/list', Controller\Stations\FilesController::class.':listAction')
                ->setName('stations:files:list');

            $this->map(['GET', 'POST'], '/batch', Controller\Stations\FilesController::class.':batchAction')
                ->setName('stations:files:batch');

            $this->map(['GET', 'POST'], '/mkdir', Controller\Stations\FilesController::class.':mkdirAction')
                ->setName('stations:files:mkdir');

            $this->map(['GET', 'POST'], '/upload', Controller\Stations\FilesController::class.':uploadAction')
                ->setName('stations:files:upload');

            $this->map(['GET', 'POST'], '/download', Controller\Stations\FilesController::class.':downloadAction')
                ->setName('stations:files:download');

        })
            ->add(Middleware\Module\StationFiles::class)
            ->add([Middleware\Permissions::class, 'manage station media', true]);

        $this->group('/playlists', function () {

            $this->get('', Controller\Stations\PlaylistsController::class.':indexAction')
                ->setName('stations:playlists:index');

            $this->map(['GET', 'POST'], '/edit[/{id}]', Controller\Stations\PlaylistsController::class.':editAction')
                ->setName('stations:playlists:edit');

            $this->get('/delete/{id}', Controller\Stations\PlaylistsController::class.':deleteAction')
                ->setName('stations:playlists:delete');

            $this->get('/export/{id}[/{format}]', Controller\Stations\PlaylistsController::class.':exportAction')
                ->setName('stations:playlists:export');

        })->add([Middleware\Permissions::class, 'manage station media', true]);

        $this->group('/mounts', function () {

            $this->get('', Controller\Stations\MountsController::class.':indexAction')
                ->setName('stations:mounts:index');

            $this->get('/migrate', Controller\Stations\MountsController::class.':migrateAction')
                ->setName('stations:mounts:migrate');

            $this->map(['GET', 'POST'], '/edit[/{id}]', Controller\Stations\MountsController::class.':editAction')
                ->setName('stations:mounts:edit');

            $this->get('/delete/{id}', Controller\Stations\MountsController::class.':deleteAction')
                ->setName('stations:mounts:delete');

        })->add([Middleware\Permissions::class, 'manage station mounts', true]);

        $this->group('/profile', function () {

            $this->get('', Controller\Stations\ProfileController::class.':indexAction')
                ->setName('stations:profile:index');

            $this->map(['GET', 'POST'], '/edit', Controller\Stations\ProfileController::class.':editAction')
                ->setName('stations:profile:edit')
                ->add([Middleware\Permissions::class, 'manage station profile', true]);

            $this->map(['GET', 'POST'], '/backend[/{do}]', Controller\Stations\ProfileController::class.':backendAction')
                ->setName('stations:profile:backend')
                ->add([Middleware\Permissions::class, 'manage station broadcasting', true]);

            $this->map(['GET', 'POST'], '/frontend[/{do}]', Controller\Stations\ProfileController::class.':frontendAction')
                ->setName('stations:profile:frontend')
                ->add([Middleware\Permissions::class, 'manage station broadcasting', true]);

        });

        $this->group('/requests', function () {

            $this->get('', Controller\Stations\RequestsController::class.':indexAction')
                ->setName('stations:requests:index');

            $this->get('/delete/{request_id}', Controller\Stations\RequestsController::class.':deleteAction')
                ->setName('stations:requests:delete');

        })->add([Middleware\Permissions::class, 'view station reports', true]);

        $this->group('/reports', function () {

            $this->get('/timeline[/format/{format}]', Controller\Stations\ReportsController::class.':timelineAction')
                ->setName('stations:reports:timeline');

            $this->get('/performance[/format/{format}]', Controller\Stations\ReportsController::class.':performanceAction')
                ->setName('stations:reports:performance');

            $this->get('/duplicates', Controller\Stations\ReportsController::class.':duplicatesAction')
                ->setName('stations:reports:duplicates');

            $this->get('/duplicates/delete/{media_id}', Controller\Stations\ReportsController::class.':deletedupeAction')
                ->setName('stations:reports:deletedupe');

            $this->map(['GET', 'POST'], '/listeners', Controller\Stations\ReportsController::class.':listenersAction')
                ->setName('stations:reports:listeners');

        })->add([Middleware\Permissions::class, 'view station reports', true]);

        $this->group('/streamers', function () {

            $this->get('', Controller\Stations\StreamersController::class.':indexAction')
                ->setName('stations:streamers:index');

            $this->map(['GET', 'POST'], '/edit[/{id}]', Controller\Stations\StreamersController::class.':editAction')
                ->setName('stations:streamers:edit');
            $this->get('/delete/{id}', Controller\Stations\StreamersController::class.':deleteAction')
                ->setName('stations:streamers:delete');

        })->add([Middleware\Permissions::class, 'manage station streamers', true]);

        $this->group('/util', function () {

            $this->get('/restart', Controller\Stations\UtilController::class.':restartAction')
                ->setName('stations:util:restart');

        })->add([Middleware\Permissions::class, 'manage station broadcasting', true]);

        // END /stations GROUP

    })
        ->add(Middleware\Module\Stations::class)
        ->add([Middleware\Permissions::class, 'view station administration', true])
        ->add(Middleware\GetStation::class)
        ->add(Middleware\EnableView::class)
        ->add(Middleware\RequireLogin::class);

};