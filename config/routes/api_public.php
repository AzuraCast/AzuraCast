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

    $group->get(
        '/nowplaying[/{station_id}]',
        Controller\Api\NowPlayingAction::class
    )->setName('api:nowplaying:index')
        ->add(new Middleware\Cache\SetCache(15))
        ->add(Middleware\GetStation::class);

    $group->get(
        '/nowplaying/{station_id}/art[/{timestamp}.jpg]',
        Controller\Api\NowPlayingArtAction::class
    )->setName('api:nowplaying:art')
        ->add(new Middleware\Cache\SetCache(15))
        ->add(Middleware\RequireStation::class)
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
            )->setName('api:stations:media:art')
                ->add(new Middleware\Cache\SetStaticFileCache());

            // Streamer Art
            $group->get(
                '/streamer/{id}/art[-{timestamp}.jpg]',
                Controller\Api\Stations\Streamers\Art\GetArtAction::class
            )->setName('api:stations:streamer:art')
                ->add(new Middleware\Cache\SetStaticFileCache());

            $group->group(
                '/public',
                function (RouteCollectorProxy $group) {
                    // Podcast Public Pages
                    $group->get('/podcasts', Controller\Api\Stations\Podcasts\ListPodcastsAction::class)
                        ->setName('api:stations:public:podcasts');

                    $group->group(
                        '/podcast/{podcast_id}',
                        function (RouteCollectorProxy $group) {
                            $group->get('', Controller\Api\Stations\Podcasts\GetPodcastAction::class)
                                ->setName('api:stations:public:podcast');

                            $group->get(
                                '/art[-{timestamp}.jpg]',
                                Controller\Api\Stations\Podcasts\Art\GetArtAction::class
                            )->setName('api:stations:public:podcast:art')
                                ->add(new Middleware\Cache\SetStaticFileCache());

                            $group->get(
                                '/episodes',
                                Controller\Api\Stations\Podcasts\Episodes\ListEpisodesAction::class
                            )->setName('api:stations:public:podcast:episodes');

                            $group->group(
                                '/episode/{episode_id}',
                                function (RouteCollectorProxy $group) {
                                    $group->get(
                                        '',
                                        Controller\Api\Stations\Podcasts\Episodes\GetEpisodeAction::class
                                    )->setName('api:stations:public:podcast:episode')
                                        ->add(new Middleware\Cache\SetStaticFileCache());

                                    $group->get(
                                        '/art[-{timestamp}.jpg]',
                                        Controller\Api\Stations\Podcasts\Episodes\Art\GetArtAction::class
                                    )->setName('api:stations:public:podcast:episode:art')
                                        ->add(new Middleware\Cache\SetStaticFileCache());

                                    $group->get(
                                        '/download[.{extension}]',
                                        Controller\Api\Stations\Podcasts\Episodes\Media\GetMediaAction::class
                                    )->setName('api:stations:public:podcast:episode:download')
                                        ->add(Middleware\RateLimit::forDownloads());
                                }
                            );
                        }
                    )->add(Middleware\RequirePublishedPodcastEpisodeMiddleware::class)
                        ->add(Middleware\GetAndRequirePodcast::class);
                }
            );
        }
    )->add(Middleware\RequireStation::class)
        ->add(Middleware\GetStation::class);
};
