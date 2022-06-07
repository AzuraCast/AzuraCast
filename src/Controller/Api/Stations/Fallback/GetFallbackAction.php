<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Fallback;

use App\Entity;
use App\Flysystem\StationFilesystems;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[OA\Get(
    path: '/station/{station_id}/fallback',
    description: 'Get the custom fallback track for a station.',
    security: OpenApi::API_KEY_SECURITY,
    tags: ['Stations: General'],
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
final class GetFallbackAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        string $station_id
    ): ResponseInterface {
        set_time_limit(600);

        $station = $request->getStation();

        $fallbackPath = $station->getFallbackPath();
        if (!empty($fallbackPath)) {
            $fsConfig = (new StationFilesystems($station))->getConfigFilesystem();

            if ($fsConfig->fileExists($fallbackPath)) {
                return $response->streamFilesystemFile(
                    $fsConfig,
                    $fallbackPath,
                    basename($fallbackPath)
                );
            }
        }

        return $response->withStatus(404)
            ->withJson(Entity\Api\Error::notFound());
    }
}
