<?php
/**
 * Shorthand controller instantiation format:
 * module:controller:action
 * i.e. frontend:index:index -> \Modules\Frontend\Controllers\IndexController::indexAction
 */

return function(\Slim\App $app) {

    $app->group('/api', function () {

        $this->get('', 'api:index:index')->setName('api:index:index');
        $this->get('/status', 'api:index:status')->setName('api:index:status');
        $this->get('/time', 'api:index:time')->setName('api:index:time');

        $this->group('/internal', function () {

            $this->group('/{station}', function() {

                $this->get('/auth', 'api:internal:auth')->setName('api:internal:auth');
                $this->get('/nextsong', 'api:internal:nextsong')->setName('api:internal:nextsong');

            });

        });

        $this->get('/nowplaying[/{station}]', 'api:nowplaying:index')->setName('api:nowplaying:index');
        $this->get('/stations', 'api:stations:list')->setName('api:stations:list');

        $this->group('/station/{station}', function () {

            $this->get('', 'api:stations:index')->setName('api:stations:index');
            $this->get('/nowplaying', 'api:nowplaying:index');

            // This would not normally be POST-able, but Bootgrid requires it
            $this->map(['GET', 'POST'], '/requests', 'api:requests:list')->setName('api:requests:list');
            $this->map(['GET', 'POST'], '/request/{song_id}', 'api:requests:submit')->setName('api:requests:submit');

            $this->get('/listeners', 'api:listeners:index')->setName('api:listeners:index');

        });

    });

};