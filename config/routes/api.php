<?php

use App\Controller;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Middleware;
use Psr\Http\Message\ResponseInterface;
use Slim\Routing\RouteCollectorProxy;

return static function (RouteCollectorProxy $app) {
    $app->group(
        '/api',
        function (RouteCollectorProxy $group) {
            $group->options(
                '/{routes:.+}',
                function (ServerRequest $request, Response $response, ...$params) {
                    return $response
                        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                        ->withHeader(
                            'Access-Control-Allow-Headers',
                            'x-api-key, x-requested-with, Content-Type, Accept, Origin, Authorization'
                        )
                        ->withHeader('Access-Control-Allow-Origin', '*');
                }
            );

            $group->get(
                '',
                function (ServerRequest $request, Response $response, ...$params): ResponseInterface {
                    return $response->withRedirect('/static/api/index.html');
                }
            )->setName('api:index:index');

            $group->get('/openapi.yml', Controller\Api\OpenApiAction::class)
                ->setName('api:openapi');

            $group->get('/status', Controller\Api\IndexController::class . ':statusAction')
                ->setName('api:index:status');

            $group->get('/time', Controller\Api\IndexController::class . ':timeAction')
                ->setName('api:index:time');

            $group->group(
                '/internal',
                function (RouteCollectorProxy $group) {
                    $group->group(
                        '/{station_id}',
                        function (RouteCollectorProxy $group) {
                            $group->map(
                                ['GET', 'POST'],
                                '/liquidsoap/{action}',
                                Controller\Api\Internal\LiquidsoapAction::class
                            )->setName('api:internal:liquidsoap');

                            // Icecast internal auth functions
                            $group->map(
                                ['GET', 'POST'],
                                '/listener-auth',
                                Controller\Api\Internal\ListenerAuthAction::class
                            )->setName('api:internal:listener-auth');
                        }
                    )->add(Middleware\GetStation::class);

                    $group->get('/relays', Controller\Api\Admin\RelaysController::class)
                        ->setName('api:internal:relays')
                        ->add(Middleware\RequireLogin::class);

                    $group->post('/relays', Controller\Api\Admin\RelaysController::class . ':updateAction')
                        ->add(Middleware\RequireLogin::class);
                }
            );

            $group->get('/nowplaying[/{station_id}]', Controller\Api\NowPlayingAction::class)
                ->setName('api:nowplaying:index');

            $group->get('/stations', Controller\Api\Stations\IndexController::class . ':listAction')
                ->setName('api:stations:list')
                ->add(new Middleware\RateLimit('api'));

            call_user_func(include(__DIR__ . '/api_admin.php'), $group);
            call_user_func(include(__DIR__ . '/api_frontend.php'), $group);
            call_user_func(include(__DIR__ . '/api_station.php'), $group);
        }
    )->add(Middleware\Module\Api::class);
};
