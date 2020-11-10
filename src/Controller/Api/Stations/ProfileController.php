<?php

namespace App\Controller\Api\Stations;

use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

class ProfileController
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        Entity\Repository\StationScheduleRepository $scheduleRepo,
        Entity\ApiGenerator\NowPlayingApiGenerator $nowPlayingApiGenerator
    ): ResponseInterface {
        $station = $request->getStation();
        $backend = $request->getStationBackend();
        $frontend = $request->getStationFrontend();

        $nowPlayingApi = $nowPlayingApiGenerator->currentOrEmpty($station);

        $apiResponse = new Entity\Api\StationProfile();
        $apiResponse->fromParentObject($nowPlayingApi);

        $apiResponse->cache = 'database';

        $apiResponse->services = new Entity\Api\StationServiceStatus(
            $backend->isRunning($station),
            $frontend->isRunning($station)
        );

        $apiResponse->schedule = $scheduleRepo->getUpcomingSchedule($station);

        $apiResponse->update();
        $apiResponse->resolveUrls($baseUri = $request->getRouter()->getBaseUrl());

        return $response->withJson($apiResponse);
    }
}
