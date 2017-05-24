<?php
/**
 * Shorthand controller instantiation format:
 * module:controller:action
 * i.e. frontend:index:index -> \Modules\Frontend\Controllers\IndexController::indexAction
 */

return function(\Slim\App $app) {

    $app->group('/station/{station}', function () {

        $this->get('', 'stations:index:index')->setName('stations:index:index');

        $this->group('/automation', function () {

            $this->map(['GET', 'POST'], '', 'stations:automation:index')->setName('stations:automation:index');
            $this->get('/run', 'stations:automation:run')->setName('stations:automation:run');

        });

        $this->group('/files', function () {

            $this->get('', 'stations:files:index')->setName('stations:files:index');
            $this->map(['GET', 'POST'], '/edit/{id}', 'stations:files:edit')->setName('stations:files:edit');
            $this->map(['GET', 'POST'], '/rename/{path}', 'stations:files:rename')->setName('stations:files:rename');
            $this->map(['GET', 'POST'], '/list', 'stations:files:list')->setName('stations:files:list');
            $this->map(['GET', 'POST'], '/batch', 'stations:files:batch')->setName('stations:files:batch');
            $this->map(['GET', 'POST'], '/mkdir', 'stations:files:mkdir')->setName('stations:files:mkdir');
            $this->map(['GET', 'POST'], '/upload', 'stations:files:upload')->setName('stations:files:upload');
            $this->map(['GET', 'POST'], '/download', 'stations:files:download')->setName('stations:files:download');

        });

        $this->group('/playlists', function () {

            $this->get('', 'stations:playlists:index')->setName('stations:playlists:index');
            $this->map(['GET', 'POST'], '/edit[/{id}]', 'stations:playlists:edit')->setName('stations:playlists:edit');
            $this->get('/delete/{id}', 'stations:playlists:delete')->setName('stations:playlists:delete');
            $this->get('/export/{id}[/{format}]', 'stations:playlists:export')->setName('stations:playlists:export');

        });

        $this->group('/mounts', function () {

            $this->get('', 'stations:mounts:index')->setName('stations:mounts:index');
            $this->map(['GET', 'POST'], '/edit[/{id}]', 'stations:mounts:edit')->setName('stations:mounts:edit');
            $this->get('/delete/{id}', 'stations:mounts:delete')->setName('stations:mounts:delete');

        });

        $this->group('/profile', function () {

            $this->get('', 'stations:profile:index')->setName('stations:profile:index');
            $this->map(['GET', 'POST'], '/edit', 'stations:profile:edit')->setName('stations:profile:edit');
            $this->map(['GET', 'POST'], '/backend[/{do}]', 'stations:profile:backend')->setName('stations:profile:backend');
            $this->map(['GET', 'POST'], '/frontend[/{do}]', 'stations:profile:frontend')->setName('stations:profile:frontend');

        });

        $this->group('/requests', function () {

            $this->get('', 'stations:requests:index')->setName('stations:requests:index');
            $this->get('/delete/{request_id}', 'stations:requests:delete')->setName('stations:requests:delete');

        });

        $this->group('/reports', function () {

            $this->get('/timeline[/format/{format}]', 'stations:index:timeline')->setName('stations:index:timeline');
            $this->get('/performance[/format/{format}]', 'stations:reports:performance')->setName('stations:reports:performance');
            $this->get('/duplicates', 'stations:reports:duplicates')->setName('stations:reports:duplicates');
            $this->get('/duplicates/delete/{media_id}', 'stations:reports:deletedupe')->setName('stations:reports:deletedupe');
            $this->map(['GET', 'POST'], '/listeners', 'stations:reports:listeners')->setName('stations:reports:listeners');
            $this->map(['GET', 'POST'], '/soundexchange', 'stations:reports:soundexchange')->setName('stations:reports:soundexchange');

        });

        $this->group('/streamers', function () {

            $this->get('', 'stations:streamers:index')->setName('stations:streamers:index');
            $this->map(['GET', 'POST'], '/edit[/{id}]', 'stations:streamers:edit')->setName('stations:streamers:edit');
            $this->get('/delete/{id}', 'stations:streamers:delete')->setName('stations:streamers:delete');

        });

        $this->group('/util', function () {

            $this->get('/playlist[/{format}]', 'stations:util:playlist')->setName('stations:util:playlist');
            $this->get('/write', 'stations:util:write')->setName('stations:util:write');
            $this->get('/restart', 'stations:util:restart')->setName('stations:util:restart');

        });

    });

};