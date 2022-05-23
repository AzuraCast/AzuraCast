<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\StereoTool;

use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[OA\Delete(
    path: '/station/{station_id}/stereo-tool-configuration',
    description: 'Removes the Stereo Tool configuration file for a station.',
    security: OpenApi::API_KEY_SECURITY,
    tags: ['Stations: Broadcasting'],
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
final class DeleteStereoToolConfigurationAction
{
    public function __construct(
        private readonly Entity\Repository\StationRepository $stationRepo
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        int|string $station_id
    ): ResponseInterface {
        $station = $request->getStation();

        $this->stationRepo->clearStereoToolConfiguration($station);

        return $response->withJson(Entity\Api\Status::deleted());
    }
}
