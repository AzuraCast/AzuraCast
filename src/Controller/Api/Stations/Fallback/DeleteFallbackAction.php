<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Fallback;

use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[OA\Delete(
    path: '/station/{station_id}/fallback',
    description: 'Removes the custom fallback track for a station.',
    security: OpenApi::API_KEY_SECURITY,
    tags: ['Stations: General'],
    parameters: [
        new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
    ],
    responses: [
        new OA\Response(ref: OpenApi::REF_RESPONSE_SUCCESS, response: 200),
        new OA\Response(ref: OpenApi::REF_RESPONSE_ACCESS_DENIED, response: 403),
        new OA\Response(ref: OpenApi::REF_RESPONSE_NOT_FOUND, response: 404),
        new OA\Response(ref: OpenApi::REF_RESPONSE_GENERIC_ERROR, response: 500),
    ]
)]
class DeleteFallbackAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        Entity\Repository\StationRepository $stationRepo
    ): ResponseInterface {
        $station = $request->getStation();
        $stationRepo->clearFallback($station);

        return $response->withJson(Entity\Api\Status::deleted());
    }
}
