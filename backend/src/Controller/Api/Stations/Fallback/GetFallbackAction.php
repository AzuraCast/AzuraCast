<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Fallback;

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
    path: '/station/{station_id}/fallback',
    operationId: 'getStationFallback',
    summary: 'Get the custom fallback track for a station.',
    tags: [OpenApi::TAG_STATIONS],
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
final class GetFallbackAction implements SingleActionInterface
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $station = $request->getStation();
        $router = $request->getRouter();

        $download = ($params['do'] ?? null) === 'download';

        $fallbackPath = $station->fallback_path;
        if (!empty($fallbackPath)) {
            $fsConfig = StationFilesystems::buildConfigFilesystem($station);
            if ($fsConfig->fileExists($fallbackPath)) {
                if ($download) {
                    set_time_limit(600);

                    return $response->streamFilesystemFile(
                        $fsConfig,
                        $fallbackPath,
                        basename($fallbackPath)
                    );
                }

                return $response->withJson(
                    new UploadedRecordStatus(
                        true,
                        $router->fromHere(
                            routeParams: ['do' => 'download']
                        )
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
                null,
            )
        );
    }
}
