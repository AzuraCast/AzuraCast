<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Playlists;

use App\Controller\SingleActionInterface;
use App\Entity\Api\StationPlaylistQueue;
use App\Entity\Enums\PlaylistOrders;
use App\Entity\Enums\PlaylistSources;
use App\Entity\Repository\StationPlaylistMediaRepository;
use App\Entity\Repository\StationPlaylistRepository;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use App\Paginator;
use InvalidArgumentException;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[OA\Get(
    path: '/station/{station_id}/playlist/{id}/queue',
    operationId: 'getStationPlaylistQueue',
    summary: 'Get the current playback queue for the specified playlist.',
    tags: [OpenApi::TAG_STATIONS_PLAYLISTS],
    parameters: [
        new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
        new OA\Parameter(
            name: 'id',
            description: 'Playlist ID',
            in: 'path',
            required: true,
            schema: new OA\Schema(type: 'integer', format: 'int64')
        ),
    ],
    responses: [
        new OpenApi\Response\Success(
            content: new OA\JsonContent(
                type: 'array',
                items: new OA\Items(
                    ref: StationPlaylistQueue::class
                )
            )
        ),
        new OpenApi\Response\AccessDenied(),
        new OpenApi\Response\NotFound(),
        new OpenApi\Response\GenericError(),
    ]
)]
final readonly class GetQueueAction implements SingleActionInterface
{
    public function __construct(
        private StationPlaylistRepository $playlistRepo,
        private StationPlaylistMediaRepository $spmRepo
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        /** @var string $id */
        $id = $params['id'];

        $record = $this->playlistRepo->requireForStation($id, $request->getStation());

        if (PlaylistSources::Songs !== $record->source) {
            throw new InvalidArgumentException('This playlist does not have songs as its primary source.');
        }

        if (PlaylistOrders::Random === $record->order) {
            throw new InvalidArgumentException('This playlist is always shuffled and has no visible queue.');
        }

        $queue = $this->spmRepo->getQueue($record);
        return Paginator::fromArray($queue, $request)->write($response);
    }
}
