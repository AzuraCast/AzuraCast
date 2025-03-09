<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations;

use App\Container\EntityManagerAwareTrait;
use App\Entity\Api\NowPlaying\Station as NowPlayingStation;
use App\Entity\ApiGenerator\StationApiGenerator;
use App\Entity\Station;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[
    OA\Get(
        path: '/stations',
        operationId: 'getStations',
        summary: 'Returns a list of stations.',
        security: [],
        tags: [OpenApi::TAG_PUBLIC_STATIONS],
        parameters: [],
        responses: [
            new OpenApi\Response\Success(
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: NowPlayingStation::class)
                )
            ),
        ]
    ),
    OA\Get(
        path: '/station/{station_id}',
        operationId: 'getStation',
        summary: 'Return information about a single station.',
        security: [],
        tags: [OpenApi::TAG_PUBLIC_STATIONS],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
        ],
        responses: [
            new OpenApi\Response\Success(
                content: new OA\JsonContent(ref: NowPlayingStation::class)
            ),
            new OpenApi\Response\NotFound(),
        ]
    )
]
final class IndexController
{
    use EntityManagerAwareTrait;

    public function __construct(
        private readonly StationApiGenerator $stationApiGenerator
    ) {
    }

    public function indexAction(
        ServerRequest $request,
        Response $response
    ): ResponseInterface {
        $station = $request->getStation();

        return $response->withJson(
            $this->stationApiGenerator->__invoke($station)
        );
    }

    public function listAction(
        ServerRequest $request,
        Response $response
    ): ResponseInterface {
        $stationsRaw = $this->em->getRepository(Station::class)
            ->findBy(['is_enabled' => 1]);

        $stations = [];
        foreach ($stationsRaw as $row) {
            /** @var Station $row */
            $apiRow = $this->stationApiGenerator->__invoke($row);

            if ($apiRow->is_public) {
                $stations[] = $apiRow;
            }
        }

        return $response->withJson($stations);
    }
}
