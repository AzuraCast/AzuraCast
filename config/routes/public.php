<?php

declare(strict_types=1);

use App\Controller;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Middleware;
use Slim\Routing\RouteCollectorProxy;

return static function (RouteCollectorProxy $app) {
    $app->get(
        '/public/sw.js',
        function (ServerRequest $request, Response $response, ...$params) {
            return $response
                ->withHeader('Content-Type', 'text/javascript')
                ->write(
                    <<<'JS'
                    self.addEventListener('install', event => {
                      // Kick out the old service worker
                      self.skipWaiting();
                    });
                    JS
                );
        }
    )->setName('public:sw');

    $app->group(
        '/public/{station_id}',
        function (RouteCollectorProxy $group) {
            $group->get('[/{embed:embed|social}]', Controller\Frontend\PublicPages\PlayerAction::class)
                ->setName('public:index');

            $group->get('/oembed/{format:json|xml}', Controller\Frontend\PublicPages\OEmbedAction::class)
                ->setName('public:oembed');

            $group->get('/app.webmanifest', Controller\Frontend\PWA\AppManifestAction::class)
                ->setName('public:manifest');

            $group->get('/embed-requests', Controller\Frontend\PublicPages\RequestsAction::class)
                ->setName('public:embedrequests');

            $group->get('/playlist[.{format}]', Controller\Frontend\PublicPages\PlaylistAction::class)
                ->setName('public:playlist');

            $group->get('/history', Controller\Frontend\PublicPages\HistoryAction::class)
                ->setName('public:history');

            $group->get('/dj', Controller\Frontend\PublicPages\WebDjAction::class)
                ->setName('public:dj');

            $group->get('/ondemand[/{embed:embed}]', Controller\Frontend\PublicPages\OnDemandAction::class)
                ->setName('public:ondemand');

            $group->get('/schedule[/{embed:embed}]', Controller\Frontend\PublicPages\ScheduleAction::class)
                ->setName('public:schedule');

            $group->get('/podcasts', Controller\Frontend\PublicPages\PodcastsAction::class)
                ->setName('public:podcasts');

            $group->get(
                '/podcast/{podcast_id}/episodes',
                Controller\Frontend\PublicPages\PodcastEpisodesAction::class
            )
                ->setName('public:podcast:episodes');

            $group->get(
                '/podcast/{podcast_id}/episode/{episode_id}',
                Controller\Frontend\PublicPages\PodcastEpisodeAction::class
            )
                ->setName('public:podcast:episode');

            $group->get('/podcast/{podcast_id}/feed', Controller\Frontend\PublicPages\PodcastFeedAction::class)
                ->setName('public:podcast:feed');
        }
    )
        ->add(Middleware\EnableView::class)
        ->add(Middleware\GetStation::class);
};
