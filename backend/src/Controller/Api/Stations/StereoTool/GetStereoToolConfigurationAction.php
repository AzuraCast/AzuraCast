<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\StereoTool;

use App\Controller\SingleActionInterface;
use App\Entity\Api\Error;
use App\Entity\Api\UploadedRecordStatus;
use App\Flysystem\StationFilesystems;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[OA\Get(
    path: '/station/{station_id}/stereo-tool-configuration',
    operationId: 'getStereoToolConfiguration',
    summary: 'Get the Stereo Tool configuration file for a station.',
    tags: [OpenApi::TAG_STATIONS_BROADCASTING],
    parameters: [
        new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
    ],
    responses: [
        new OpenApi\Response\Success(
            content: new OA\JsonContent(
                ref: UploadedRecordStatus::class
            )
        ),
        new OpenApi\Response\AccessDenied(),
        new OpenApi\Response\NotFound(),
        new OpenApi\Response\GenericError(),
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

        $stereoToolConfigurationPath = $station->backend_config->stereo_tool_configuration_path;

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

                return $response->withJson(
                    new UploadedRecordStatus(
                        true,
                        $router->fromHere(routeParams: ['do' => 'download'])
                    )
                );
            }
        }

        if ($download) {
            return $response->withStatus(404)
                ->withJson(Error::notFound());
        }

        return $response->withJson(
            new UploadedRecordStatus(
                false,
                null
            )
        );
    }
}
