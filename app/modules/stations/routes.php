<?php
/**
 * Shorthand controller instantiation format:
 * module:controller:action
 * i.e. frontend:index:index -> \Modules\Frontend\Controllers\IndexController::indexAction
 */

$app->group('/station/{station}', function() {

    $this->any('', 'stations:index:index')
        ->setName('stations:index:index');

    $this->group('/automation', function() {

        $this->any('', 'stations:automation:index')
            ->setName('stations:automation:index');

        $this->any('/run', 'stations:automation:run')
            ->setName('stations:automation:run');

    });

    $this->group('/files', function() {

        $this->any('', 'stations:files:index')
            ->setName('stations:files:index');

        $this->any('/edit/{id}', 'stations:files:edit')
            ->setName('stations:files:edit');

        $this->any('/list', 'stations:files:list')
            ->setName('stations:files:list');

        $this->any('/batch', 'stations:files:batch')
            ->setName('stations:files:batch');

        $this->any('/mkdir', 'stations:files:mkdir')
            ->setName('stations:files:mkdir');

        $this->any('/upload', 'stations:files:upload')
            ->setName('stations:files:upload');

        $this->any('/download', 'stations:files:download')
            ->setName('stations:files:download');

    });

    $this->group('/playlists', function() {

        $this->any('', 'stations:playlists:index')
            ->setName('stations:playlists:index');

        $this->any('/edit[/{id}]', 'stations:playlists:edit')
            ->setName('stations:playlists:edit');

        $this->any('/delete/{id}', 'stations:playlists:delete')
            ->setName('stations:playlists:delete');

    });

    $this->group('/profile', function() {

        $this->any('', 'stations:profile:index')
            ->setName('stations:profile:index');

        $this->any('/edit', 'stations:profile:edit')
            ->setName('stations:profile:edit');

        $this->any('/backend[/{do}]', 'stations:profile:backend')
            ->setName('stations:profile:backend');

        $this->any('/frontend[/{do}]', 'stations:profile:frontend')
            ->setName('stations:profile:frontend');

    });

    $this->group('/reports', function() {

        $this->any('/timeline[/format/{format}]', 'stations:index:timeline')
            ->setName('stations:index:timeline');

        $this->any('/performance[/format/{format}]', 'stations:reports:performance')
            ->setName('stations:reports:performance');

        $this->any('/duplicates', 'stations:reports:duplicates')
            ->setName('stations:reports:duplicates');

        $this->any('/duplicates/delete/{media_id}', 'stations:reports:deletedupe')
            ->setName('stations:reports:deletedupe');

    });

    $this->group('/streamers', function() {

        $this->any('', 'stations:streamers:index')
            ->setName('stations:streamers:index');

        $this->any('/edit[/{id}]', 'stations:streamers:edit')
            ->setName('stations:streamers:edit');

        $this->any('/delete/{id}', 'stations:streamers:delete')
            ->setName('stations:streamers:delete');

    });

    $this->group('/util', function() {

        $this->any('/playlist[/{format}]', 'stations:util:playlist')
            ->setName('stations:util:playlist');

        $this->any('/write', 'stations:util:write')
            ->setName('stations:util:write');

        $this->any('/restart', 'stations:util:restart')
            ->setName('stations:util:restart');

    });

});