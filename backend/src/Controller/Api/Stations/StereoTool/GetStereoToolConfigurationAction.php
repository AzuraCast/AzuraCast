<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\StereoTool;

use App\Controller\SingleActionInterface;
use App\Entity\Api\Error;
use App\Flysystem\StationFilesystems;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[OA\Get(
    path: '/station/{station_id}/stereo-tool-configuration',
    operationId: 'getStereoToolConfiguration',
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
final class GetStereoToolConfigurationAction implements SingleActionInterface
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $router = $request->getRouter();
        $station = $request->getStation();

        $download = ($params['do'] ?? null) === 'download';

        $stereoToolConfigurationPath = $station->getBackendConfig()->getStereoToolConfigurationPath();

        if (!empty($stereoToolConfigurationPath)) {
            $fsConfig = StationFilesystems::buildConfigFilesystem($station);
            if ($fsConfig->fileExists($stereoToolConfigurationPath)) {
                if ($download) {
                    set_time_limit(600);

                    return $response->streamFilesystemFile(
                        $fsConfig,
                        $stereoToolConfigurationPath,
                        basename($stereoToolConfigurationPath)
                    );
                }

                return $response->withJson([
                    'hasRecord' => true,
                    'links' => [
                        'download' => $router->fromHere(routeParams: ['do' => 'download']),
                    ],
                ]);
            }
        }

        if ($download) {
            return $response->withStatus(404)
                ->withJson(Error::notFound());
        }

        return $response->withJson([
            'hasRecord' => false,
            'links' => [
                'download' => null,
            ],
        ]);
    }
}
