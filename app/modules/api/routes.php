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

                $this->map(['GET', 'POST'], '/auth', 'api:internal:auth')->setName('api:internal:auth');
                $this->map(['GET', 'POST'], '/nextsong', 'api:internal:nextsong')->setName('api:internal:nextsong');

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

    })->add(function (\Slim\Http\Request $request, \Slim\Http\Response $response, callable $next) use ($app) {

        $di = $app->getContainer();

        /** @var \App\Session $session */
        $session = $di->get('session');

        if (!$session->exists()) {
            $session->disable();
        }

        $response = $response->withHeader('Cache-Control', 'public, max-age=' . 30)
            ->withHeader('X-Accel-Expires', 30) // CloudFlare caching
            ->withHeader('Access-Control-Allow-Origin', '*');

        // Custom error handling for API responses.
        try {
            return $next($request, $response);
        } catch(\Exception $e) {

            $return_data = [
                'type' => get_class($e),
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
            ];

            if (!APP_IN_PRODUCTION) {
                $return_data['stack_trace'] = $e->getTrace();
            }

            return $response->withStatus(500)->write(json_encode($return_data));
        }

    });

};