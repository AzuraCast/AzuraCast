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
use App\OpenApi;
use App\Radio\Adapters;
use GuzzleHttp\Psr7\Uri;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[
    OA\Get(
        path: '/station/{station_id}/profile',
        operationId: 'getStationProfile',
        summary: 'Retrieve the profile of the given station.',
        tags: [OpenApi::TAG_STATIONS],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
        ],
        responses: [
            new OpenApi\Response\Success(
                content: new OA\JsonContent(ref: StationProfile::class)
            ),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\NotFound(),
            new OpenApi\Response\GenericError(),
        ]
    )
]
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

        $apiResponse->station = $this->stationApiGenerator->__invoke($station, $baseUri, true);

        $apiResponse->services = new StationServiceStatus(
            null !== $backend && $backend->isRunning($station),
            null !== $frontend && $frontend->isRunning($station),
            $station->has_started,
            $station->needs_restart
        );

        $apiResponse->schedule = $this->scheduleRepo->getUpcomingSchedule($station);

        return $response->withJson($apiResponse);
    }
}
