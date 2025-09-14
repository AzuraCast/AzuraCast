<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Playlists;

use App\Controller\SingleActionInterface;
use App\Entity\Api\Status;
use App\Entity\Repository\StationPlaylistMediaRepository;
use App\Entity\Repository\StationPlaylistRepository;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Message\WritePlaylistFileMessage;
use App\OpenApi;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Messenger\MessageBus;

#[OA\Put(
    path: '/station/{station_id}/playlist/{id}/reshuffle',
    operationId: 'putReshufflePlaylist',
    summary: 'Re-shuffle a playlist whose playback order is "shuffled".',
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
        new OpenApi\Response\Success(),
        new OpenApi\Response\AccessDenied(),
        new OpenApi\Response\NotFound(),
        new OpenApi\Response\GenericError(),
    ]
)]
final readonly class ReshuffleAction implements SingleActionInterface
{
    public function __construct(
        private StationPlaylistRepository $playlistRepo,
        private StationPlaylistMediaRepository $spmRepo,
        private MessageBus $messageBus,
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

        $this->spmRepo->resetQueue($record);

        // Write changes to file.
        $message = new WritePlaylistFileMessage();
        $message->playlist_id = $record->id;

        $this->messageBus->dispatch($message);

        return $response->withJson(
            new Status(
                true,
                __('Playlist reshuffled.')
            )
        );
    }
}
