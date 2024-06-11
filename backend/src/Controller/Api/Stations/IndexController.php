<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations;

use App\Container\EntityManagerAwareTrait;
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
        description: 'Returns a list of stations.',
        tags: ['Stations: General'],
        parameters: [],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/Api_NowPlaying_Station')
                )
            ),
        ]
    ),
    OA\Get(
        path: '/station/{station_id}',
        operationId: 'getStation',
        description: 'Return information about a single station.',
        tags: ['Stations: General'],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(ref: '#/components/schemas/Api_NowPlaying_Station')
            ),
            new OA\Response(ref: OpenApi::REF_RESPONSE_NOT_FOUND, response: 404),
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

        $apiResponse = ($this->stationApiGenerator)($station);
        $apiResponse->resolveUrls($request->getRouter()->getBaseUrl());

        return $response->withJson($apiResponse);
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
            $apiRow = ($this->stationApiGenerator)($row);
            $apiRow->resolveUrls($request->getRouter()->getBaseUrl());

            if ($apiRow->is_public) {
                $stations[] = $apiRow;
            }
        }

        return $response->withJson($stations);
    }
}
