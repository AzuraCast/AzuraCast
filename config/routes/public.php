<?php

use App\Controller;
use App\Middleware;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app) {

    $app->group('/public/{station_id}', function (RouteCollectorProxy $group) {

        $group->get('', Controller\Frontend\PublicController::class . ':indexAction')
            ->setName('public:index');

        $group->get('/embed', Controller\Frontend\PublicController::class . ':embedAction')
            ->setName('public:embed');

        $group->get('/embed-requests', Controller\Frontend\PublicController::class . ':embedrequestsAction')
            ->setName('public:embedrequests');

        $group->get('/playlist[/{format}]', Controller\Frontend\PublicController::class . ':playlistAction')
            ->setName('public:playlist');

        $group->get('/dj', Controller\Frontend\PublicController::class . ':djAction')
            ->setName('public:dj');

    })
        ->add(Middleware\GetStation::class)
        ->add(Middleware\EnableView::class);

};