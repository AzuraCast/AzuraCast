<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Mounts\Intro;

use App\Controller\SingleActionInterface;
use App\Entity\Api\Status;
use App\Entity\Repository\StationMountRepository;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use App\Service\Flow;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[OA\Post(
    path: '/station/{station_id}/mount/{id}/intro',
    operationId: 'postMountIntro',
    summary: 'Update the intro track for a mount point.',
    requestBody: new OA\RequestBody(ref: OpenApi::REF_REQUEST_BODY_FLOW_FILE_UPLOAD),
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
        new OpenApi\Response\Success(),
        new OpenApi\Response\AccessDenied(),
        new OpenApi\Response\NotFound(),
        new OpenApi\Response\GenericError(),
    ]
)]
final readonly class PostIntroAction implements SingleActionInterface
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
        /** @var string|null $id */
        $id = $params['id'] ?? null;

        $station = $request->getStation();

        $flowResponse = Flow::process($request, $response, $station->getRadioTempDir());
        if ($flowResponse instanceof ResponseInterface) {
            return $flowResponse;
        }

        if (null !== $id) {
            $mount = $this->mountRepo->requireForStation($id, $station);
            $this->mountRepo->setIntro($mount, $flowResponse);

            return $response->withJson(Status::updated());
        }

        return $response->withJson($flowResponse);
    }
}
