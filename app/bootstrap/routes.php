<?php
/**
 * Shorthand controller instantiation format:
 * module:controller:action
 * i.e. frontend:index:index -> \Modules\Frontend\Controllers\IndexController::indexAction
 */

$app->group('/admin', function() {

    $this->get('', 'admin:index:index')
        ->setName('admin:index:index');

    $this->get('/sync/{type}', 'admin:index:sync')
        ->setName('admin:index:sync');

    $this->group('/api', function() {

        $this->get('', 'admin:api:index')
            ->setName('admin:api:index');

        $this->map(['GET', 'POST'], '/edit[/{id}]', 'admin:api:edit')
            ->setName('admin:api:edit');

        $this->get('/delete/{id}', 'admin:api:delete')
            ->setName('admin:api:delete');

    });

    $this->group('/permissions', function() {

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

    $this->group('/stations', function() {

        $this->get('', 'admin:stations:index')
            ->setName('admin:stations:index');

        $this->map(['GET', 'POST'], '/edit[/{id}]', 'admin:stations:edit')
            ->setName('admin:stations:edit');

        $this->get('/delete/{id}', 'admin:stations:delete')
            ->setName('admin:stations:delete');

    });

    $this->group('/users', function() {

        $this->get('', 'admin:users:index')
            ->setName('admin:users:index');

        $this->map(['GET', 'POST'], '/edit[/{id}]', 'admin:users:edit')
            ->setName('admin:users:edit');

        $this->get('/delete/{id}', 'admin:users:delete')
            ->setName('admin:users:delete');

        $this->get('/login-as/{id}', 'admin:users:impersonate')
            ->setName('admin:users:impersonate');

    });

});

$app->group('/api', function() {

    $this->map(['GET', 'POST'], '', 'api:index:index')
        ->setName('api:index:index');

    $this->map(['GET', 'POST'], '/status', 'api:index:status')
        ->setName('api:index:status');

    $this->map(['GET', 'POST'], '/time', 'api:index:time')
        ->setName('api:index:time');

    $this->group('/internal', function() {

        $this->map(['GET', 'POST'], '/streamauth/{id}', 'api:internal:streamauth')
            ->setName('api:internal:streamauth');

    });

    $this->group('/nowplaying[/{id}]', function() {

        $this->map(['GET', 'POST'], '', 'api:nowplaying:index')
            ->setName('api:nowplaying:index');

    });

    $this->group('/requests/{station}', function() {

        $this->map(['GET', 'POST'], '/list', 'api:requests:list')
            ->setName('api:requests:list');

        $this->map(['GET', 'POST'], '/submit/{song_id}', 'api:requests:submit')
            ->setName('api:requests:submit');

    });

    $this->group('/stations', function() {

        $this->map(['GET', 'POST'], '', 'api:stations:list')
            ->setName('api:stations:list');

        $this->map(['GET', 'POST'], '/{id}', 'api:stations:index')
            ->setName('api:stations:index');

    });

});

$app->get('/', 'frontend:index:index')
    ->setName('home');

$app->get('/account', 'frontend:account:index')
    ->setName('account:index');

$app->map(['GET', 'POST'], '/login', 'frontend:account:login')
    ->setName('account:login');

$app->get('/logout', 'frontend:account:logout')
    ->setName('account:logout');

$app->get('/endsession', 'frontend:account:endmasquerade')
    ->setName('account:endmasquerade');

$app->get('/profile', 'frontend:profile:index')
    ->setName('profile:index');

$app->map(['GET', 'POST'], '/profile/edit', 'frontend:profile:edit')
    ->setName('profile:edit');

$app->map(['GET', 'POST'], '/profile/timezone', 'frontend:profile:timezone')
    ->setName('profile:timezone');

$app->group('/setup', function() {

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

$app->group('/public', function() {

    $this->get('[/{station}]', 'frontend:public:index')
        ->setName('public:index');

});

$app->get('/test', 'frontend:util:test')
    ->setName('util:test');

$app->group('/station/{station}', function() {

    $this->get('', 'stations:index:index')
        ->setName('stations:index:index');

    $this->group('/automation', function() {

        $this->map(['GET', 'POST'], '', 'stations:automation:index')
            ->setName('stations:automation:index');

        $this->get('/run', 'stations:automation:run')
            ->setName('stations:automation:run');

    });

    $this->group('/files', function() {

        $this->get('', 'stations:files:index')
            ->setName('stations:files:index');

        $this->map(['GET', 'POST'], '/edit/{id}', 'stations:files:edit')
            ->setName('stations:files:edit');

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

    $this->group('/playlists', function() {

        $this->get('', 'stations:playlists:index')
            ->setName('stations:playlists:index');

        $this->map(['GET', 'POST'], '/edit[/{id}]', 'stations:playlists:edit')
            ->setName('stations:playlists:edit');

        $this->get('/delete/{id}', 'stations:playlists:delete')
            ->setName('stations:playlists:delete');

    });

    $this->group('/mounts', function() {

        $this->get('', 'stations:mounts:index')
            ->setName('stations:mounts:index');

        $this->map(['GET', 'POST'], '/edit[/{id}]', 'stations:mounts:edit')
            ->setName('stations:mounts:edit');

        $this->get('/delete/{id}', 'stations:mounts:delete')
            ->setName('stations:mounts:delete');

    });

    $this->group('/profile', function() {

        $this->get('', 'stations:profile:index')
            ->setName('stations:profile:index');

        $this->map(['GET', 'POST'], '/edit', 'stations:profile:edit')
            ->setName('stations:profile:edit');

        $this->map(['GET', 'POST'], '/backend[/{do}]', 'stations:profile:backend')
            ->setName('stations:profile:backend');

        $this->map(['GET', 'POST'], '/frontend[/{do}]', 'stations:profile:frontend')
            ->setName('stations:profile:frontend');

    });

    $this->group('/reports', function() {

        $this->get('/timeline[/format/{format}]', 'stations:index:timeline')
            ->setName('stations:index:timeline');

        $this->get('/performance[/format/{format}]', 'stations:reports:performance')
            ->setName('stations:reports:performance');

        $this->get('/duplicates', 'stations:reports:duplicates')
            ->setName('stations:reports:duplicates');

        $this->get('/duplicates/delete/{media_id}', 'stations:reports:deletedupe')
            ->setName('stations:reports:deletedupe');

    });

    $this->group('/streamers', function() {

        $this->get('', 'stations:streamers:index')
            ->setName('stations:streamers:index');

        $this->map(['GET', 'POST'], '/edit[/{id}]', 'stations:streamers:edit')
            ->setName('stations:streamers:edit');

        $this->get('/delete/{id}', 'stations:streamers:delete')
            ->setName('stations:streamers:delete');

    });

    $this->group('/util', function() {

        $this->get('/playlist[/{format}]', 'stations:util:playlist')
            ->setName('stations:util:playlist');

        $this->get('/write', 'stations:util:write')
            ->setName('stations:util:write');

        $this->get('/restart', 'stations:util:restart')
            ->setName('stations:util:restart');

    });

});