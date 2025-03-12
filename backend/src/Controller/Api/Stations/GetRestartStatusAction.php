<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations;

use App\Controller\SingleActionInterface;
use App\Entity\Api\StationRestartStatus;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[
    OA\Get(
        path: '/station/{station_id}/restart-status',
        operationId: 'getRestartStatus',
        summary: 'Get the pending restart status for a station.',
        tags: [OpenApi::TAG_STATIONS],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
        ],
        responses: [
            new OpenApi\Response\Success(
                content: new OA\JsonContent(
                    ref: StationRestartStatus::class,
                )
            ),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\GenericError(),
        ]
    ),
]
final class GetRestartStatusAction implements SingleActionInterface
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $station = $request->getStation();

        return $response->withJson(StationRestartStatus::fromStation($station));
    }
}
