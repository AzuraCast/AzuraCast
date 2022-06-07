<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations;

use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\ResponseInterface;

final class ProfileAction
{
    public function __construct(
        private readonly Entity\Repository\StationScheduleRepository $scheduleRepo,
        private readonly Entity\ApiGenerator\NowPlayingApiGenerator $nowPlayingApiGenerator,
        private readonly Entity\ApiGenerator\StationApiGenerator $stationApiGenerator
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        string $station_id
    ): ResponseInterface {
        $station = $request->getStation();
        $backend = $request->getStationBackend();
        $frontend = $request->getStationFrontend();

        $baseUri = new Uri('');
        $nowPlayingApi = $this->nowPlayingApiGenerator->currentOrEmpty($station, $baseUri);

        $apiResponse = new Entity\Api\StationProfile();
        $apiResponse->fromParentObject($nowPlayingApi);

        $apiResponse->station = ($this->stationApiGenerator)($station, $baseUri, true);
        $apiResponse->cache = 'database';

        $apiResponse->services = new Entity\Api\StationServiceStatus(
            $backend->isRunning($station),
            $frontend->isRunning($station),
            $station->getHasStarted(),
            $station->getNeedsRestart()
        );

        $apiResponse->schedule = $this->scheduleRepo->getUpcomingSchedule($station);

        $apiResponse->update();
        $apiResponse->resolveUrls($request->getRouter()->getBaseUrl());

        return $response->withJson($apiResponse);
    }
}
