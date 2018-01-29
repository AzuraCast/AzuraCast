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

    })->add(Middleware\RequireLogin::class)
        ->add([Middleware\Permissions::class, 'view administration'])
        ->add(Middleware\Module\Admin::class);

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

    })->add(Middleware\Module\Api::class);

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

    })->add(Middleware\RequireLogin::class);

    $app->map(['GET', 'POST'], '/login', 'frontend:account:login')
        ->setName('account:login');

    $app->group('/setup', function () {

        $this->map(['GET', 'POST'], '', 'frontend:setup:index')
            ->setName('setup:index');

        $this->map(['GET', 'POST'], '/complete', 'frontend:setup:complete')
            ->setName('setup:complete');

        $this->map(['GET', 'POST'], '/register', 'frontend:setup:register')
            ->setName('setup:register');

        $this->map(['GET', 'POST'], '/station', 'frontend:setup:station')
            ->setName('setup:station');

        $this->map(['GET', 'POST'], '/settings', 'frontend:setup:settings')
            ->setName('setup:settings');

    });

    $app->group('/public/{station}', function () {

        $this->get('', 'frontend:public:index')
            ->setName('public:index');

        $this->get('/embed', 'frontend:public:embed')
            ->setName('public:embed');

        $this->get('/embed-requests', 'frontend:public:embedrequests')
            ->setName('public:embedrequests');

        $this->get('/playlist[/{format}]', 'frontend:public:playlist')
            ->setName('public:playlist');

    });

    $app->group('/station/{station}', function () {

        $this->get('', 'stations:index:index')
            ->setName('stations:index:index');

        $this->group('/automation', function () {

            $this->map(['GET', 'POST'], '', 'stations:automation:index')
                ->setName('stations:automation:index');

            $this->get('/run', 'stations:automation:run')
                ->setName('stations:automation:run');

        });

        $this->group('/files', function () {

            $this->get('', 'stations:files:index')
                ->setName('stations:files:index');

            $this->map(['GET', 'POST'], '/edit/{id}', 'stations:files:edit')
                ->setName('stations:files:edit');

            $this->map(['GET', 'POST'], '/rename/{path}', 'stations:files:rename')
                ->setName('stations:files:rename');

            $this->map(['GET', 'POST'], '/list', 'stations:files:list')
                ->setName('stations:files:list');

            $this->map(['GET', 'POST'], '/batch', 'stations:files:batch')
                ->setName('stations:files:batch');

            $this->map(['GET', 'POST'], '/mkdir', 'stations:files:mkdir')
                ->setName('stations:files:mkdir');

            $this->map(['GET', 'POST'], '/upload', 'stations:files:upload')
                ->setName('stations:files:upload');

            $this->map(['GET', 'POST'], '/download', 'stations:files:download')
                ->setName('stations:files:download');

        });

        $this->group('/playlists', function () {

            $this->get('', 'stations:playlists:index')
                ->setName('stations:playlists:index');

            $this->map(['GET', 'POST'], '/edit[/{id}]', 'stations:playlists:edit')
                ->setName('stations:playlists:edit');

            $this->get('/delete/{id}', 'stations:playlists:delete')
                ->setName('stations:playlists:delete');

            $this->get('/export/{id}[/{format}]', 'stations:playlists:export')
                ->setName('stations:playlists:export');

        });

        $this->group('/mounts', function () {

            $this->get('', 'stations:mounts:index')
                ->setName('stations:mounts:index');

            $this->get('/migrate', 'stations:mounts:migrate')
                ->setName('stations:mounts:migrate');

            $this->map(['GET', 'POST'], '/edit[/{id}]', 'stations:mounts:edit')
                ->setName('stations:mounts:edit');

            $this->get('/delete/{id}', 'stations:mounts:delete')
                ->setName('stations:mounts:delete');

        });

        $this->group('/profile', function () {

            $this->get('', 'stations:profile:index')
                ->setName('stations:profile:index');

            $this->map(['GET', 'POST'], '/edit', 'stations:profile:edit')
                ->setName('stations:profile:edit');

            $this->map(['GET', 'POST'], '/backend[/{do}]', 'stations:profile:backend')
                ->setName('stations:profile:backend');

            $this->map(['GET', 'POST'], '/frontend[/{do}]', 'stations:profile:frontend')
                ->setName('stations:profile:frontend');

        });

        $this->group('/requests', function () {

            $this->get('', 'stations:requests:index')
                ->setName('stations:requests:index');

            $this->get('/delete/{request_id}', 'stations:requests:delete')
                ->setName('stations:requests:delete');

        });

        $this->group('/reports', function () {

            $this->get('/timeline[/format/{format}]', 'stations:reports:timeline')
                ->setName('stations:reports:timeline');

            $this->get('/performance[/format/{format}]', 'stations:reports:performance')
                ->setName('stations:reports:performance');

            $this->get('/duplicates', 'stations:reports:duplicates')
                ->setName('stations:reports:duplicates');

            $this->get('/duplicates/delete/{media_id}', 'stations:reports:deletedupe')
                ->setName('stations:reports:deletedupe');

            $this->map(['GET', 'POST'], '/listeners', 'stations:reports:listeners')
                ->setName('stations:reports:listeners');

        });

        $this->group('/streamers', function () {

            $this->get('', 'stations:streamers:index')
                ->setName('stations:streamers:index');

            $this->map(['GET', 'POST'], '/edit[/{id}]', 'stations:streamers:edit')
                ->setName('stations:streamers:edit');
            $this->get('/delete/{id}', 'stations:streamers:delete')
                ->setName('stations:streamers:delete');

        });

        $this->group('/util', function () {

            $this->get('/restart', 'stations:util:restart')
                ->setName('stations:util:restart');

        });

        // END /stations GROUP

    })->add(Middleware\RequireLogin::class);

};