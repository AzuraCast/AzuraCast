<?php

declare(strict_types=1);

use App\Controller;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Middleware;
use Psr\Http\Message\ResponseInterface;
use Slim\Routing\RouteCollectorProxy;

// Public-facing API endpoints (unauthenticated).
return static function (RouteCollectorProxy $group) {
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
            return $response->withRedirect('/docs/api/');
        }
    )->setName('api:index:index');

    if (App\Environment::getInstance()->isDevelopment()) {
        $group->get('/openapi.yml', Controller\Api\OpenApiDevAction::class)
            ->setName('api:openapi');
    } else {
        $group->get('/openapi.yml', Controller\Api\OpenApiPublicAction::class)
            ->setName('api:openapi')
            ->add(new Middleware\Cache\SetCache(Middleware\Cache\SetCache::CACHE_ONE_DAY));
    }

    $group->get('/status', Controller\Api\IndexController::class . ':statusAction')
        ->setName('api:index:status');

    $group->get('/time', Controller\Api\IndexController::class . ':timeAction')
        ->setName('api:index:time')
        ->add(new Middleware\Cache\SetCache(1));

    $group->get(
        '/nowplaying',
        Controller\Api\NowPlayingAction::class
    )->setName('api:nowplaying:index')
        ->add(new Middleware\Cache\SetCache(15))
        ->add(Middleware\GetStation::class);

    $group->get('/stations', Controller\Api\Stations\IndexController::class . ':listAction')
        ->setName('api:stations:list')
        ->add(new Middleware\RateLimit('api'));
};
