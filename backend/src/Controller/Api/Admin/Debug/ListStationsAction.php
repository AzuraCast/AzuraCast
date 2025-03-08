<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin\Debug;

use App\Controller\SingleActionInterface;
use App\Entity\Repository\StationRepository;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[
    OA\Get(
        path: '/admin/debug/stations',
        operationId: 'getAdminDebugStations',
        description: 'List all stations with their debug links.',
        tags: [OpenApi::TAG_ADMIN_DEBUG],
        responses: [
            // TODO API Response Body
            new OpenApi\Response\Success(),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\NotFound(),
            new OpenApi\Response\GenericError(),
        ]
    )
]
final class ListStationsAction implements SingleActionInterface
{
    public function __construct(
        private readonly StationRepository $stationRepo
    ) {
    }

    public function __invoke(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $router = $request->getRouter();

        $stations = [];
        foreach ($this->stationRepo->fetchArray() as $station) {
            $stations[] = [
                'id' => $station['id'],
                'name' => $station['name'],
                'clearQueueUrl' => $router->named(
                    'api:admin:debug:clear-station-queue',
                    ['station_id' => $station['id']]
                ),
                'getNextSongUrl' => $router->named(
                    'api:admin:debug:nextsong',
                    ['station_id' => $station['id']]
                ),
                'getNowPlayingUrl' => $router->named(
                    'api:admin:debug:nowplaying',
                    ['station_id' => $station['id']]
                ),
            ];
        }

        return $response->withJson($stations);
    }
}
