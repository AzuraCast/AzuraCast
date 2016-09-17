<?php
/**
 * Shorthand controller instantiation format:
 * module:controller:action
 * i.e. frontend:index:index -> \Modules\Frontend\Controllers\IndexController::indexAction
 */

$app->group('/api', function() {

    $this->any('', 'api:index:index')
        ->setName('api:index:index');

    $this->any('/status', 'api:index:status')
        ->setName('api:index:status');

    $this->any('/time', 'api:index:time')
        ->setName('api:index:time');

    $this->group('/internal', function() {

        $this->any('/streamauth', 'api:internal:streamauth')
            ->setName('api:internal:streamauth');

    });

    $this->group('/nowplaying', function() {

        $this->any('', 'api:nowplaying:index')
            ->setName('api:nowplaying:index');

    });

    $this->group('/requests/{station}', function() {

        $this->any('/list', 'api:requests:list')
            ->setName('api:requests:list');

        $this->any('/submit/{song_id}', 'api:requests:submit')
            ->setName('api:requests:submit');

    });

    $this->group('/stations', function() {

        $this->any('', 'api:stations:list')
            ->setName('api:stations:list');

        $this->any('/{id}', 'api:stations:index')
            ->setName('api:stations:index');

    });

});