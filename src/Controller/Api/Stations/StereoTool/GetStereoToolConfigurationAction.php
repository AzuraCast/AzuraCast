<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\StereoTool;

use App\Entity;
use App\Flysystem\StationFilesystems;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[OA\Get(
    path: '/station/{station_id}/stereo-tool-configuration',
    description: 'Get the Stereo Tool configuration file for a station.',
    security: OpenApi::API_KEY_SECURITY,
    tags: ['Stations: Broadcasting'],
    parameters: [
        new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Success'
        ),
        new OA\Response(ref: OpenApi::REF_RESPONSE_ACCESS_DENIED, response: 403),
        new OA\Response(ref: OpenApi::REF_RESPONSE_NOT_FOUND, response: 404),
        new OA\Response(ref: OpenApi::REF_RESPONSE_GENERIC_ERROR, response: 500),
    ]
)]
final class GetStereoToolConfigurationAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        string $station_id
    ): ResponseInterface {
        set_time_limit(600);

        $station = $request->getStation();

        $stereoToolConfigurationPath = $station->getBackendConfig()->getStereoToolConfigurationPath();

        if (!empty($stereoToolConfigurationPath)) {
            $fsConfig = (new StationFilesystems($station))->getConfigFilesystem();

            if ($fsConfig->fileExists($stereoToolConfigurationPath)) {
                return $response->streamFilesystemFile(
                    $fsConfig,
                    $stereoToolConfigurationPath,
                    basename($stereoToolConfigurationPath)
                );
            }
        }

        return $response->withStatus(404)
            ->withJson(Entity\Api\Error::notFound());
    }
}
