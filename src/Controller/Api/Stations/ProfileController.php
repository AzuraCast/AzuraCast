<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations;

use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\ResponseInterface;

class ProfileController
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        Entity\Repository\StationScheduleRepository $scheduleRepo,
        Entity\ApiGenerator\NowPlayingApiGenerator $nowPlayingApiGenerator,
        Entity\ApiGenerator\StationApiGenerator $stationApiGenerator
    ): ResponseInterface {
        $station = $request->getStation();
        $backend = $request->getStationBackend();
        $frontend = $request->getStationFrontend();

        $baseUri = new Uri('');
        $nowPlayingApi = $nowPlayingApiGenerator->currentOrEmpty($station, $baseUri);

        $apiResponse = new Entity\Api\StationProfile();
        $apiResponse->fromParentObject($nowPlayingApi);

        $apiResponse->station = ($stationApiGenerator)($station, $baseUri, true);
        $apiResponse->cache = 'database';

        $apiResponse->services = new Entity\Api\StationServiceStatus(
            $backend->isRunning($station),
            $frontend->isRunning($station)
        );

        $apiResponse->schedule = $scheduleRepo->getUpcomingSchedule($station);

        $apiResponse->update();
        $apiResponse->resolveUrls($request->getRouter()->getBaseUrl());

        return $response->withJson($apiResponse);
    }
}
