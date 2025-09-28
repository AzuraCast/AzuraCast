<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Streamers\Art;

use App\Controller\SingleActionInterface;
use App\Entity\Api\Status;
use App\Entity\Repository\StationStreamerRepository;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[OA\Delete(
    path: '/station/{station_id}/streamer/{id}/art',
    operationId: 'deleteStreamerArt',
    summary: 'Removes the default album art for a streamer.',
    tags: [OpenApi::TAG_STATIONS_STREAMERS],
    parameters: [
        new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
        new OA\Parameter(
            name: 'id',
            description: 'Streamer ID',
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
final readonly class DeleteArtAction implements SingleActionInterface
{
    public function __construct(
        private StationStreamerRepository $streamerRepo
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        /** @var string $id */
        $id = $params['id'];

        $station = $request->getStation();

        $streamer = $this->streamerRepo->requireForStation($id, $station);

        $this->streamerRepo->removeArtwork($streamer);
        $this->streamerRepo->getEntityManager()
            ->flush();

        return $response->withJson(Status::deleted());
    }
}
