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

            $this->get('', 'admin:api:index')
                ->setName('admin:api:index');

            $this->map(['GET', 'POST'], '/edit[/{id}]', 'admin:api:edit')
                ->setName('admin:api:edit');

            $this->get('/delete/{id}', 'admin:api:delete')
                ->setName('admin:api:delete');

        });

        $this->group('/permissions', function () {

            $this->get('', 'admin:permissions:index')
                ->setName('admin:permissions:index');

            $this->map(['GET', 'POST'], '/edit[/{id}]', 'admin:permissions:edit')
                ->setName('admin:permissions:edit');

            $this->get('/delete/{id}', 'admin:permissions:delete')
                ->setName('admin:permissions:delete');

            $this->get('/members/{id}', 'admin:permissions:members')
                ->setName('admin:permissions:members');

        });

        $this->map(['GET', 'POST'], '/settings', 'admin:settings:index')
            ->setName('admin:settings:index');

        $this->map(['GET', 'POST'], '/branding', 'admin:branding:index')
            ->setName('admin:branding:index');

        $this->group('/stations', function () {

            $this->get('', 'admin:stations:index')
                ->setName('admin:stations:index');

            $this->map(['GET', 'POST'], '/edit[/{id}]', 'admin:stations:edit')
                ->setName('admin:stations:edit');

            $this->map(['GET', 'POST'], '/clone/{id}', 'admin:stations:clone')
                ->setName('admin:stations:clone');

            $this->get('/delete/{id}', 'admin:stations:delete')
                ->setName('admin:stations:delete');

        });

        $this->group('/users', function () {

            $this->get('', 'admin:users:index')
                ->setName('admin:users:index');

            $this->map(['GET', 'POST'], '/edit[/{id}]', 'admin:users:edit')
                ->setName('admin:users:edit');

            $this->get('/delete/{id}', 'admin:users:delete')
                ->setName('admin:users:delete');

            $this->get('/login-as/{id}', 'admin:users:impersonate')
                ->setName('admin:users:impersonate');

        });

        // END /admin GROUP

    })->add(Middleware\RequireLogin::class)
        ->add([Middleware\Permissions::class, 'view administration'])
        ->add(Middleware\Module\Admin::class);

    $app->group('/api', function () {

        $this->get('', 'api:index:index')
            ->setName('api:index:index');

        $this->get('/status', 'api:index:status')
            ->setName('api:index:status');

        $this->get('/time', 'api:index:time')
            ->setName('api:index:time');

        $this->group('/internal', function () {

            $this->group('/{station}', function() {

                $this->map(['GET', 'POST'], '/auth', 'api:internal:auth')
                    ->setName('api:internal:auth');

                $this->map(['GET', 'POST'], '/nextsong', 'api:internal:nextsong')
                    ->setName('api:internal:nextsong');

                $this->map(['GET', 'POST'], '/notify', 'api:internal:notify')
                    ->setName('api:internal:notify');

            });

        });

        $this->get('/nowplaying[/{station}]', 'api:nowplaying:index')
            ->setName('api:nowplaying:index');

        $this->get('/stations', 'api:stations:list')
            ->setName('api:stations:list');

        $this->group('/station/{station}', function () {

            $this->get('', 'api:stations:index')
                ->setName('api:stations:index');

            $this->get('/nowplaying', 'api:nowplaying:index');

            // This would not normally be POST-able, but Bootgrid requires it
            $this->map(['GET', 'POST'], '/requests', 'api:requests:list')
                ->setName('api:requests:list');

            $this->map(['GET', 'POST'], '/request/{media_id}', 'api:requests:submit')
                ->setName('api:requests:submit');

            $this->get('/listeners', 'api:listeners:index')
                ->setName('api:listeners:index');

            $this->get('/art/{media_id}', 'api:media:art')
                ->setName('api:media:art');

        });

        // END /api GROUP

    })->add(Middleware\Module\Api::class);

    $app->group('', function() {

        $this->get('/', Controller\Frontend\IndexController::class.':indexAction')
            ->setName('home');

        $this->get('/logout', 'frontend:account:logout')
            ->setName('account:logout');

        $this->get('/endsession', 'frontend:account:endmasquerade')
            ->setName('account:endmasquerade');

        $this->get('/profile', 'frontend:profile:index')
            ->setName('profile:index');

        $this->map(['GET', 'POST'], '/profile/edit', 'frontend:profile:edit')
            ->setName('profile:edit');

        // Used for internal development
        if (!APP_IN_PRODUCTION) {
            $this->any('/test', 'frontend:util:test')
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