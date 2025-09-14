<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin\Debug;

use App\Controller\SingleActionInterface;
use App\Entity\Api\Admin\Debug\DebugStation;
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
        summary: 'List all stations with their debug links.',
        tags: [OpenApi::TAG_ADMIN_DEBUG],
        responses: [
            new OpenApi\Response\Success(
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        ref: DebugStation::class
                    )
                )
            ),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\NotFound(),
            new OpenApi\Response\GenericError(),
        ]
    )
]
final readonly class ListStationsAction implements SingleActionInterface
{
    public function __construct(
        private StationRepository $stationRepo
    ) {
    }

    public function __invoke(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $router = $request->getRouter();

        $stations = [];
        foreach ($this->stationRepo->fetchArray() as $station) {
            $stations[] = new DebugStation(
                $station['id'],
                $station['name'],
                $router->named(
                    'api:admin:debug:clear-station-queue',
                    ['station_id' => $station['id']]
                ),
                $router->named(
                    'api:admin:debug:nextsong',
                    ['station_id' => $station['id']]
                ),
                $router->named(
                    'api:admin:debug:nowplaying',
                    ['station_id' => $station['id']]
                )
            );
        }

        return $response->withJson($stations);
    }
}
