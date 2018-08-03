<?php
return function(\Slim\App $app) {

    $app->group('/admin', function () {

        $this->get('', App\Controller\Admin\IndexController::class.':indexAction')
            ->setName('admin:index:index');

        $this->get('/sync/{type}', App\Controller\Admin\IndexController::class.':syncAction')
            ->setName('admin:index:sync')
            ->add([App\Middleware\Permissions::class, 'administer all'])
            ->add(App\Middleware\DebugEcho::class);

        $this->group('/api', function () {

            $this->get('', App\Controller\Admin\ApiController::class.':indexAction')
                ->setName('admin:api:index');

            $this->map(['GET', 'POST'], '/edit/{id}', App\Controller\Admin\ApiController::class.':editAction')
                ->setName('admin:api:edit');

            $this->get('/delete/{id}/{csrf}', App\Controller\Admin\ApiController::class.':deleteAction')
                ->setName('admin:api:delete');

        })->add([App\Middleware\Permissions::class, 'administer api keys']);

        $this->map(['GET', 'POST'], '/branding', App\Controller\Admin\BrandingController::class.':indexAction')
            ->setName('admin:branding:index')
            ->add([App\Middleware\Permissions::class, 'administer settings']);

        $this->group('/custom_fields', function() {

            $this->get('', App\Controller\Admin\CustomFieldsController::class.':indexAction')
                ->setName('admin:custom_fields:index');

            $this->map(['GET', 'POST'], '/edit/{id}', App\Controller\Admin\CustomFieldsController::class.':editAction')
                ->setName('admin:custom_fields:edit');

            $this->map(['GET', 'POST'], '/add', App\Controller\Admin\CustomFieldsController::class.':editAction')
                ->setName('admin:custom_fields:add');

            $this->get('/delete/{id}/{csrf}', App\Controller\Admin\CustomFieldsController::class.':deleteAction')
                ->setName('admin:custom_fields:delete');

        })->add([App\Middleware\Permissions::class, 'administer custom fields']);

        $this->group('/permissions', function () {

            $this->get('', App\Controller\Admin\PermissionsController::class.':indexAction')
                ->setName('admin:permissions:index');

            $this->map(['GET', 'POST'], '/edit/{id}', App\Controller\Admin\PermissionsController::class.':editAction')
                ->setName('admin:permissions:edit');

            $this->map(['GET', 'POST'], '/add', App\Controller\Admin\PermissionsController::class.':editAction')
                ->setName('admin:permissions:add');

            $this->get('/delete/{id}/{csrf}', App\Controller\Admin\PermissionsController::class.':deleteAction')
                ->setName('admin:permissions:delete');

        })->add([App\Middleware\Permissions::class, 'administer permissions']);

        $this->map(['GET', 'POST'], '/settings', App\Controller\Admin\SettingsController::class.':indexAction')
            ->setName('admin:settings:index')
            ->add([App\Middleware\Permissions::class, 'administer settings']);

        $this->group('/stations', function () {

            $this->get('', App\Controller\Admin\StationsController::class.':indexAction')
                ->setName('admin:stations:index');

            $this->map(['GET', 'POST'], '/edit/{id}', App\Controller\Admin\StationsController::class.':editAction')
                ->setName('admin:stations:edit');

            $this->map(['GET', 'POST'], '/add', App\Controller\Admin\StationsController::class.':editAction')
                ->setName('admin:stations:add');

            $this->map(['GET', 'POST'], '/clone/{id}', App\Controller\Admin\StationsController::class.':cloneAction')
                ->setName('admin:stations:clone');

            $this->get('/delete/{id}/{csrf}', App\Controller\Admin\StationsController::class.':deleteAction')
                ->setName('admin:stations:delete');

        })->add([App\Middleware\Permissions::class, 'administer stations']);

        $this->group('/users', function () {

            $this->get('', App\Controller\Admin\UsersController::class.':indexAction')
                ->setName('admin:users:index');

            $this->map(['GET', 'POST'], '/edit/{id}', App\Controller\Admin\UsersController::class.':editAction')
                ->setName('admin:users:edit');

            $this->map(['GET', 'POST'], '/add', App\Controller\Admin\UsersController::class.':editAction')
                ->setName('admin:users:add');

            $this->get('/delete/{id}/{csrf}', App\Controller\Admin\UsersController::class.':deleteAction')
                ->setName('admin:users:delete');

            $this->get('/login-as/{id}/{csrf}', App\Controller\Admin\UsersController::class.':impersonateAction')
                ->setName('admin:users:impersonate');

        })->add([App\Middleware\Permissions::class, 'administer users']);

        // END /admin GROUP

    })
        ->add(App\Middleware\Module\Admin::class)
        ->add(App\Middleware\EnableView::class)
        ->add([App\Middleware\Permissions::class, 'view administration'])
        ->add(App\Middleware\RequireLogin::class);

    $app->group('/api', function () {

        $this->get('', App\Controller\Api\IndexController::class.':indexAction')
            ->setName('api:index:index');

        $this->get('/status', App\Controller\Api\IndexController::class.':statusAction')
            ->setName('api:index:status');

        $this->get('/time', App\Controller\Api\IndexController::class.':timeAction')
            ->setName('api:index:time');

        $this->group('/internal', function () {

            $this->group('/{station}', function() {

                // Liquidsoap internal authentication functions
                $this->map(['GET', 'POST'], '/auth', App\Controller\Api\InternalController::class.':authAction')
                    ->setName('api:internal:auth');

                $this->map(['GET', 'POST'], '/nextsong', App\Controller\Api\InternalController::class.':nextsongAction')
                    ->setName('api:internal:nextsong');

                $this->map(['GET', 'POST'], '/djon', App\Controller\Api\InternalController::class.':djonAction')
                    ->setName('api:internal:djon');

                $this->map(['GET', 'POST'], '/djoff', App\Controller\Api\InternalController::class.':djoffAction')
                    ->setName('api:internal:djoff');

                // Station-watcher connection endpoint
                $this->map(['GET', 'POST'], '/notify', App\Controller\Api\InternalController::class.':notifyAction')
                    ->setName('api:internal:notify');

            })->add(App\Middleware\GetStation::class);

        });

        $this->get('/nowplaying[/{station}]', App\Controller\Api\NowplayingController::class.':indexAction')
            ->setName('api:nowplaying:index');

        $this->get('/stations', App\Controller\Api\Stations\IndexController::class.':listAction')
            ->setName('api:stations:list')
            ->add([App\Middleware\RateLimit::class, 'api', 5, 2]);

        $this->group('/station/{station}', function () {

            $this->get('', App\Controller\Api\Stations\IndexController::class.':indexAction')
                ->setName('api:stations:index')
                ->add([App\Middleware\RateLimit::class, 'api', 5, 2]);

            $this->get('/nowplaying', App\Controller\Api\NowplayingController::class.':indexAction');

            // This would not normally be POST-able, but Bootgrid requires it
            $this->map(['GET', 'POST'], '/requests', App\Controller\Api\RequestsController::class.':listAction')
                ->setName('api:requests:list');

            $this->map(['GET', 'POST'], '/request/{media_id}', App\Controller\Api\RequestsController::class.':submitAction')
                ->setName('api:requests:submit')
                ->add([App\Middleware\RateLimit::class, 'api', 5, 2]);

            $this->get('/listeners', App\Controller\Api\ListenersController::class.':indexAction')
                ->setName('api:listeners:index')
                ->add([App\Middleware\Permissions::class, 'view station reports', true]);

            $this->get('/art/{media_id}', App\Controller\Api\Stations\MediaController::class.':artAction')
                ->setName('api:stations:media:art');

            $this->post('/backend/{do}', App\Controller\Api\Stations\ServicesController::class.':backendAction')
                ->setName('api:stations:backend')
                ->add([App\Middleware\Permissions::class, 'manage station broadcasting', true]);

            $this->post('/frontend/{do}', App\Controller\Api\Stations\ServicesController::class.':frontendAction')
                ->setName('api:stations:frontend')
                ->add([App\Middleware\Permissions::class, 'manage station broadcasting', true]);

            $this->post('/restart', App\Controller\Api\Stations\ServicesController::class.':restartAction')
                ->setName('api:stations:restart')
                ->add([App\Middleware\Permissions::class, 'manage station broadcasting', true]);

        })->add(App\Middleware\GetStation::class);

        // END /api GROUP

    })
        ->add(App\Middleware\Module\Api::class);

    $app->get('/', App\Controller\Frontend\IndexController::class.':indexAction')
        ->setName('home');

    $app->group('', function() {

        $this->get('/dashboard', App\Controller\Frontend\DashboardController::class.':indexAction')
            ->setName('dashboard');

        $this->get('/logout', App\Controller\Frontend\AccountController::class.':logoutAction')
            ->setName('account:logout');

        $this->get('/endsession', App\Controller\Frontend\AccountController::class.':endmasqueradeAction')
            ->setName('account:endmasquerade');

        $this->get('/profile', App\Controller\Frontend\ProfileController::class.':indexAction')
            ->setName('profile:index');

        $this->map(['GET', 'POST'], '/profile/edit', App\Controller\Frontend\ProfileController::class.':editAction')
            ->setName('profile:edit');

        $this->get('/api_keys', App\Controller\Frontend\ApiKeysController::class.':indexAction')
            ->setName('api_keys:index');

        $this->map(['GET', 'POST'], '/api_keys/edit/{id}', App\Controller\Frontend\ApiKeysController::class.':editAction')
            ->setName('api_keys:edit');

        $this->map(['GET', 'POST'], '/api_keys/add', App\Controller\Frontend\ApiKeysController::class.':editAction')
            ->setName('api_keys:add');

        $this->get('/api_keys/delete/{id}/{csrf}', App\Controller\Frontend\ApiKeysController::class.':deleteAction')
            ->setName('api_keys:delete');

        // Used for internal development
        if (!APP_IN_PRODUCTION) {
            $this->any('/test', App\Controller\Frontend\UtilController::class.':testAction')
                ->setName('util:test')
                ->add(App\Middleware\DebugEcho::class);
        }

    })
        ->add(App\Middleware\EnableView::class)
        ->add(App\Middleware\RequireLogin::class);

    $app->map(['GET', 'POST'], '/login', App\Controller\Frontend\AccountController::class.':loginAction')
        ->setName('account:login')
        ->add(App\Middleware\EnableView::class);

    $app->group('/setup', function () {

        $this->map(['GET', 'POST'], '', App\Controller\Frontend\SetupController::class.':indexAction')
            ->setName('setup:index');

        $this->map(['GET', 'POST'], '/complete', App\Controller\Frontend\SetupController::class.':completeAction')
            ->setName('setup:complete');

        $this->map(['GET', 'POST'], '/register', App\Controller\Frontend\SetupController::class.':registerAction')
            ->setName('setup:register');

        $this->map(['GET', 'POST'], '/station', App\Controller\Frontend\SetupController::class.':stationAction')
            ->setName('setup:station');

        $this->map(['GET', 'POST'], '/settings', App\Controller\Frontend\SetupController::class.':settingsAction')
            ->setName('setup:settings');

    })
        ->add(App\Middleware\EnableView::class);

    $app->group('/public/{station}', function () {

        $this->get('[/{autoplay:autoplay}]', App\Controller\Frontend\PublicController::class.':indexAction')
            ->setName('public:index');

        $this->get('/embed[/{autoplay:autoplay}]', App\Controller\Frontend\PublicController::class.':embedAction')
            ->setName('public:embed');

        $this->get('/embed-requests', App\Controller\Frontend\PublicController::class.':embedrequestsAction')
            ->setName('public:embedrequests');

        $this->get('/playlist[/{format}]', App\Controller\Frontend\PublicController::class.':playlistAction')
            ->setName('public:playlist');

    })
        ->add(App\Middleware\GetStation::class)
        ->add(App\Middleware\EnableView::class);

    $app->group('/station/{station}', function () {

        $this->get('', App\Controller\Stations\IndexController::class.':indexAction')
            ->setName('stations:index:index');

        $this->group('/automation', function () {

            $this->map(['GET', 'POST'], '', App\Controller\Stations\AutomationController::class.':indexAction')
                ->setName('stations:automation:index');

            $this->get('/run', App\Controller\Stations\AutomationController::class.':runAction')
                ->setName('stations:automation:run');

        })->add([App\Middleware\Permissions::class, 'manage station automation', true]);

        $this->group('/files', function () {

            $this->get('', App\Controller\Stations\Files\FilesController::class.':indexAction')
                ->setName('stations:files:index');

            $this->map(['GET', 'POST'], '/edit/{id}', App\Controller\Stations\Files\EditController::class.':editAction')
                ->setName('stations:files:edit');

            $this->map(['GET', 'POST'], '/rename/{path}', App\Controller\Stations\Files\FilesController::class.':renameAction')
                ->setName('stations:files:rename');

            $this->map(['GET', 'POST'], '/list', App\Controller\Stations\Files\FilesController::class.':listAction')
                ->setName('stations:files:list');

            $this->map(['GET', 'POST'], '/batch', App\Controller\Stations\Files\FilesController::class.':batchAction')
                ->setName('stations:files:batch');

            $this->map(['GET', 'POST'], '/mkdir', App\Controller\Stations\Files\FilesController::class.':mkdirAction')
                ->setName('stations:files:mkdir');

            $this->map(['GET', 'POST'], '/upload', App\Controller\Stations\Files\FilesController::class.':uploadAction')
                ->setName('stations:files:upload');

            $this->map(['GET', 'POST'], '/download', App\Controller\Stations\Files\FilesController::class.':downloadAction')
                ->setName('stations:files:download');

        })
            ->add(App\Middleware\Module\StationFiles::class)
            ->add([App\Middleware\Permissions::class, 'manage station media', true]);

        $this->group('/playlists', function () {

            $this->get('', App\Controller\Stations\PlaylistsController::class.':indexAction')
                ->setName('stations:playlists:index');

            $this->get('/schedule', App\Controller\Stations\PlaylistsController::class.':scheduleAction')
                ->setName('stations:playlists:schedule');

            $this->map(['GET', 'POST'], '/edit/{id}', App\Controller\Stations\PlaylistsController::class.':editAction')
                ->setName('stations:playlists:edit');

            $this->map(['GET', 'POST'], '/add', App\Controller\Stations\PlaylistsController::class.':editAction')
                ->setName('stations:playlists:add');

            $this->get('/delete/{id}/{csrf}', App\Controller\Stations\PlaylistsController::class.':deleteAction')
                ->setName('stations:playlists:delete');

            $this->map(['GET', 'POST'], '/reorder/{id}', App\Controller\Stations\PlaylistsController::class.':reorderAction')
                ->setName('stations:playlists:reorder');

            $this->get('/export/{id}[/{format}]', App\Controller\Stations\PlaylistsController::class.':exportAction')
                ->setName('stations:playlists:export');

        })->add([App\Middleware\Permissions::class, 'manage station media', true]);

        $this->group('/mounts', function () {

            $this->get('', App\Controller\Stations\MountsController::class.':indexAction')
                ->setName('stations:mounts:index');

            $this->get('/migrate', App\Controller\Stations\MountsController::class.':migrateAction')
                ->setName('stations:mounts:migrate');

            $this->map(['GET', 'POST'], '/edit/{id}', App\Controller\Stations\MountsController::class.':editAction')
                ->setName('stations:mounts:edit');

            $this->map(['GET', 'POST'], '/add', App\Controller\Stations\MountsController::class.':editAction')
                ->setName('stations:mounts:add');

            $this->get('/delete/{id}/{csrf}', App\Controller\Stations\MountsController::class.':deleteAction')
                ->setName('stations:mounts:delete');

        })->add([App\Middleware\Permissions::class, 'manage station mounts', true]);

        $this->group('/profile', function () {

            $this->get('', App\Controller\Stations\ProfileController::class.':indexAction')
                ->setName('stations:profile:index');

            $this->map(['GET', 'POST'], '/edit', App\Controller\Stations\ProfileController::class.':editAction')
                ->setName('stations:profile:edit')
                ->add([App\Middleware\Permissions::class, 'manage station profile', true]);

        });

        $this->group('/requests', function () {

            $this->get('', App\Controller\Stations\RequestsController::class.':indexAction')
                ->setName('stations:requests:index');

            $this->get('/delete/{request_id}/{csrf}', App\Controller\Stations\RequestsController::class.':deleteAction')
                ->setName('stations:requests:delete');

        })->add([App\Middleware\Permissions::class, 'view station reports', true]);

        $this->group('/reports', function () {

            $this->get('/timeline[/format/{format}]', App\Controller\Stations\ReportsController::class.':timelineAction')
                ->setName('stations:reports:timeline');

            $this->get('/performance[/format/{format}]', App\Controller\Stations\ReportsController::class.':performanceAction')
                ->setName('stations:reports:performance');

            $this->get('/duplicates', App\Controller\Stations\ReportsController::class.':duplicatesAction')
                ->setName('stations:reports:duplicates');

            $this->get('/duplicates/delete/{media_id}', App\Controller\Stations\ReportsController::class.':deletedupeAction')
                ->setName('stations:reports:deletedupe');

            $this->map(['GET', 'POST'], '/listeners', App\Controller\Stations\ReportsController::class.':listenersAction')
                ->setName('stations:reports:listeners');

        })->add([App\Middleware\Permissions::class, 'view station reports', true]);

        $this->group('/streamers', function () {

            $this->get('', App\Controller\Stations\StreamersController::class.':indexAction')
                ->setName('stations:streamers:index');

            $this->map(['GET', 'POST'], '/edit/{id}', App\Controller\Stations\StreamersController::class.':editAction')
                ->setName('stations:streamers:edit');

            $this->map(['GET', 'POST'], '/add', App\Controller\Stations\StreamersController::class.':editAction')
                ->setName('stations:streamers:add');

            $this->get('/delete/{id}/{csrf}', App\Controller\Stations\StreamersController::class.':deleteAction')
                ->setName('stations:streamers:delete');

        })->add([App\Middleware\Permissions::class, 'manage station streamers', true]);

        $this->group('/webhooks', function () {

            $this->get('', App\Controller\Stations\WebhooksController::class.':indexAction')
                ->setName('stations:webhooks:index');

            $this->map(['GET', 'POST'], '/edit/{id}', App\Controller\Stations\WebhooksController::class.':editAction')
                ->setName('stations:webhooks:edit');

            $this->map(['GET', 'POST'], '/add[/{type}]', App\Controller\Stations\WebhooksController::class.':addAction')
                ->setName('stations:webhooks:add');

            $this->get('/toggle/{id}/{csrf}', App\Controller\Stations\WebhooksController::class.':toggleAction')
                ->setName('stations:webhooks:toggle');

            $this->get('/delete/{id}/{csrf}', App\Controller\Stations\WebhooksController::class.':deleteAction')
                ->setName('stations:webhooks:delete');

        })->add([App\Middleware\Permissions::class, 'manage station web hooks', true]);

        // END /stations GROUP

    })
        ->add(App\Middleware\Module\Stations::class)
        ->add([App\Middleware\Permissions::class, 'view station management', true])
        ->add(App\Middleware\GetStation::class)
        ->add(App\Middleware\EnableView::class)
        ->add(App\Middleware\RequireLogin::class);

};
