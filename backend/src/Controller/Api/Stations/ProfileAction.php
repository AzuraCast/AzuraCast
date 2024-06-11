<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations;

use App\Controller\SingleActionInterface;
use App\Entity\Api\StationProfile;
use App\Entity\Api\StationServiceStatus;
use App\Entity\ApiGenerator\StationApiGenerator;
use App\Entity\Repository\StationScheduleRepository;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Radio\Adapters;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\ResponseInterface;

final class ProfileAction implements SingleActionInterface
{
    public function __construct(
        private readonly StationScheduleRepository $scheduleRepo,
        private readonly StationApiGenerator $stationApiGenerator,
        private readonly Adapters $adapters,
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $station = $request->getStation();
        $backend = $this->adapters->getBackendAdapter($station);
        $frontend = $this->adapters->getFrontendAdapter($station);

        $baseUri = new Uri('');

        $apiResponse = new StationProfile();

        $apiResponse->station = ($this->stationApiGenerator)($station, $baseUri, true);

        $apiResponse->services = new StationServiceStatus(
            null !== $backend && $backend->isRunning($station),
            null !== $frontend && $frontend->isRunning($station),
            $station->getHasStarted(),
            $station->getNeedsRestart()
        );

        $apiResponse->schedule = $this->scheduleRepo->getUpcomingSchedule($station);

        $apiResponse->resolveUrls($request->getRouter()->getBaseUrl());

        return $response->withJson($apiResponse);
    }
}
