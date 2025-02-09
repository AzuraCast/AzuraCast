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
        description: 'Get the pending restart status for a station.',
        security: OpenApi::API_KEY_SECURITY,
        tags: ['Stations: General'],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/Api_StationRestartStatus',
                )
            ),
            new OA\Response(ref: OpenApi::REF_RESPONSE_ACCESS_DENIED, response: 403),
            new OA\Response(ref: OpenApi::REF_RESPONSE_GENERIC_ERROR, response: 500),
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
