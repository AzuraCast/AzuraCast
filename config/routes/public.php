<?php

use App\Controller;
use App\Middleware;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app) {

    $app->group('/public/{station_id}', function (RouteCollectorProxy $group) {

        $group->get('[/{embed:embed}]', Controller\Frontend\PublicPages\PlayerAction::class)
            ->setName('public:index');

        $group->get('/embed-requests', Controller\Frontend\PublicPages\RequestsAction::class)
            ->setName('public:embedrequests');

        $group->get('/playlist[/{format}]', Controller\Frontend\PublicPages\PlaylistAction::class)
            ->setName('public:playlist');

        $group->get('/dj', Controller\Frontend\PublicPages\WebDjAction::class)
            ->setName('public:dj');

        $group->get('/ondemand[/{embed:embed}]', Controller\Frontend\PublicPages\OnDemandAction::class)
            ->setName('public:ondemand');

    })
        ->add(Middleware\GetStation::class)
        ->add(Middleware\EnableView::class);
};