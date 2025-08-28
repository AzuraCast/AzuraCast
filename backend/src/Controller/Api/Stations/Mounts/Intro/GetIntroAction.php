<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Mounts\Intro;

use App\Controller\SingleActionInterface;
use App\Entity\Api\Error;
use App\Entity\Repository\StationMountRepository;
use App\Entity\StationMount;
use App\Flysystem\StationFilesystems;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[OA\Get(
    path: '/station/{station_id}/mount/{id}/intro',
    operationId: 'getMountIntro',
    summary: 'Get the intro track for a mount point.',
    tags: [OpenApi::TAG_STATIONS_MOUNT_POINTS],
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
        new OpenApi\Response\SuccessWithDownload(),
        new OpenApi\Response\AccessDenied(),
        new OpenApi\Response\NotFound(),
        new OpenApi\Response\GenericError(),
    ]
)]
final readonly class GetIntroAction implements SingleActionInterface
{
    public function __construct(
        private StationMountRepository $mountRepo,
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        set_time_limit(600);

        /** @var string $id */
        $id = $params['id'];

        $station = $request->getStation();
        $mount = $this->mountRepo->findForStation($id, $station);

        if ($mount instanceof StationMount) {
            $introPath = $mount->intro_path;

            if (!empty($introPath)) {
                $fsConfig = StationFilesystems::buildConfigFilesystem($station);
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
            ->withJson(Error::notFound());
    }
}
