<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Playlists;

use App\Controller\SingleActionInterface;
use App\Entity\Api\Status;
use App\Entity\Enums\PlaylistSources;
use App\Entity\Repository\StationPlaylistMediaRepository;
use App\Entity\Repository\StationPlaylistRepository;
use App\Exception;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Message\WritePlaylistFileMessage;
use App\OpenApi;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Messenger\MessageBus;

#[OA\Delete(
    path: '/station/{station_id}/playlist/{id}/empty',
    operationId: 'deleteEmptyPlaylist',
    summary: 'Empty the contents of a song-based playlist.',
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
final readonly class EmptyAction implements SingleActionInterface
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

        if (PlaylistSources::Songs !== $record->source) {
            throw new Exception(__('This playlist is not song-based.'));
        }

        $this->spmRepo->emptyPlaylist($record);

        // Write changes to file.
        $message = new WritePlaylistFileMessage();
        $message->playlist_id = $record->id;

        $this->messageBus->dispatch($message);

        return $response->withJson(
            new Status(
                true,
                __('Playlist emptied.')
            )
        );
    }
}
