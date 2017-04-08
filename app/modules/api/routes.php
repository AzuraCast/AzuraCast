<?php
/**
 * Shorthand controller instantiation format:
 * module:controller:action
 * i.e. frontend:index:index -> \Modules\Frontend\Controllers\IndexController::indexAction
 */

return function(\Slim\App $app) {

    $app->group('/api', function () {

        $this->map(['GET', 'POST'], '', 'api:index:index')->setName('api:index:index');
        $this->map(['GET', 'POST'], '/status', 'api:index:status')->setName('api:index:status');
        $this->map(['GET', 'POST'], '/time', 'api:index:time')->setName('api:index:time');

        $this->group('/internal', function () {

            $this->map(['GET', 'POST'], '/streamauth/{id}', 'api:internal:streamauth')->setName('api:internal:streamauth');

        });

        $this->group('/nowplaying[/{id}]', function () {

            $this->map(['GET', 'POST'], '', 'api:nowplaying:index')->setName('api:nowplaying:index');

        });

        $this->group('/requests/{station}', function () {

            $this->map(['GET', 'POST'], '/list', 'api:requests:list')->setName('api:requests:list');
            $this->map(['GET', 'POST'], '/submit/{song_id}', 'api:requests:submit')->setName('api:requests:submit');

        });

        $this->group('/stations', function () {

            $this->map(['GET', 'POST'], '', 'api:stations:list')->setName('api:stations:list');
            $this->map(['GET', 'POST'], '/{id}', 'api:stations:index')->setName('api:stations:index');

        });

    });

};