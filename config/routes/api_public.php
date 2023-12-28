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

    $group->get('/openapi.yml', Controller\Api\OpenApiAction::class)
        ->setName('api:openapi')
        ->add(new Middleware\Cache\SetCache(60));

    $group->get('/status', Controller\Api\IndexController::class . ':statusAction')
        ->setName('api:index:status');

    $group->get('/time', Controller\Api\IndexController::class . ':timeAction')
        ->setName('api:index:time')
        ->add(new Middleware\Cache\SetCache(1));

    $group->group(
        '/nowplaying/{station_id}',
        function (RouteCollectorProxy $group) {
            $group->get(
                '',
                Controller\Api\NowPlayingAction::class
            )->setName('api:nowplaying:index');

            $group->get(
                '/art[/{timestamp}.jpg]',
                Controller\Api\NowPlayingArtAction::class
            )->setName('api:nowplaying:art')
                ->add(Middleware\RequireStation::class);
        }
    )->add(new Middleware\Cache\SetCache(15))
        ->add(Middleware\GetStation::class);

    $group->get('/stations', Controller\Api\Stations\IndexController::class . ':listAction')
        ->setName('api:stations:list')
        ->add(new Middleware\RateLimit('api'));

    $group->group(
        '/station/{station_id}',
        function (RouteCollectorProxy $group) {
            // Media Art
            $group->get(
                '/art/{media_id:[a-zA-Z0-9\-]+}[-{timestamp}.jpg]',
                Controller\Api\Stations\Art\GetArtAction::class
            )->setName('api:stations:media:art');

            // Streamer Art
            $group->get(
                '/streamer/{id}/art[-{timestamp}.jpg]',
                Controller\Api\Stations\Streamers\Art\GetArtAction::class
            )->setName('api:stations:streamer:art');

            // Podcast and Episode Art
            $group->group(
                '/podcast/{podcast_id}',
                function (RouteCollectorProxy $group) {
                    $group->get(
                        '/art[-{timestamp}.jpg]',
                        Controller\Api\Stations\Podcasts\Art\GetArtAction::class
                    )->setName('api:stations:podcast:art');

                    $group->get(
                        '/episode/{episode_id}/art[-{timestamp}.jpg]',
                        Controller\Api\Stations\Podcasts\Episodes\Art\GetArtAction::class
                    )->setName('api:stations:podcast:episode:art');
                }
            )->add(Middleware\RequirePublishedPodcastEpisodeMiddleware::class);
        }
    )->add(new Middleware\Cache\SetStaticFileCache())
        ->add(Middleware\RequireStation::class)
        ->add(Middleware\GetStation::class);
};
