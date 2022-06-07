<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Mounts\Intro;

use App\Entity;
use App\Flysystem\StationFilesystems;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[OA\Get(
    path: '/station/{station_id}/mount/{id}/intro',
    description: 'Get the intro track for a mount point.',
    security: OpenApi::API_KEY_SECURITY,
    tags: ['Stations: Mount Points'],
    parameters: [
        new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
        new OA\Parameter(
            name: 'id',
            description: 'Mount Point ID',
            in: 'path',
            required: true,
            schema: new OA\Schema(type: 'integer', format: 'int64')
        ),
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
final class GetIntroAction
{
    public function __construct(
        private readonly Entity\Repository\StationMountRepository $mountRepo,
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        string $station_id,
        string $id
    ): ResponseInterface {
        set_time_limit(600);

        $station = $request->getStation();
        $mount = $this->mountRepo->findForStation($id, $station);

        if ($mount instanceof Entity\StationMount) {
            $introPath = $mount->getIntroPath();

            if (!empty($introPath)) {
                $fsConfig = (new StationFilesystems($station))->getConfigFilesystem();

                if ($fsConfig->fileExists($introPath)) {
                    return $response->streamFilesystemFile(
                        $fsConfig,
                        $introPath,
                        basename($introPath)
                    );
                }
            }
        }

        return $response->withStatus(404)
            ->withJson(Entity\Api\Error::notFound());
    }
}
