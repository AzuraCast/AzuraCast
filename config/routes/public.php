<?php

use App\Controller;
use App\Middleware;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app) {
    $app->get('/sw.js', Controller\Frontend\PWA\ServiceWorkerAction::class)
        ->setName('public:sw');

    $app->group(
        '/public/{station_id}',
        function (RouteCollectorProxy $group) {
            $group->get('[/{embed:embed|social}]', Controller\Frontend\PublicPages\PlayerAction::class)
                ->setName('public:index');

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

            $group->get('/podcasts', Controller\Frontend\PublicPages\PodcastsController::class)
                ->setName('public:podcasts');

            $group->get('/podcast/{podcast_id}/episodes', Controller\Frontend\PublicPages\PodcastEpisodesController::class)
                ->setName('public:podcast:episodes');

            $group->get('/podcast/{podcast_id}/episode/{episode_id}', Controller\Frontend\PublicPages\PodcastEpisodeController::class)
                ->setName('public:podcast:episode');

            $group->get('/podcast/{podcast_id}/feed', Controller\Frontend\PublicPages\PodcastFeedController::class)
                ->setName('public:podcast:feed');
        }
    )
        ->add(Middleware\GetStation::class)
        ->add(Middleware\EnableView::class);
};
