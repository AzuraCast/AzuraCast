<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Playlists;

use App\Controller\SingleActionInterface;
use App\Entity\Enums\PlaylistOrders;
use App\Entity\Enums\PlaylistSources;
use App\Entity\Repository\StationPlaylistMediaRepository;
use App\Entity\Repository\StationPlaylistRepository;
use App\Exception;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Message\WritePlaylistFileMessage;
use App\OpenApi;
use App\Utilities\Types;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Messenger\MessageBus;

#[OA\Put(
    path: '/station/{station_id}/playlist/{id}/order',
    operationId: 'putStationPlaylistOrder',
    summary: 'Set the order of sequential tracks in the specified playlist.',
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
        // TODO API Response
        new OpenApi\Response\Success(),
        new OpenApi\Response\AccessDenied(),
        new OpenApi\Response\NotFound(),
        new OpenApi\Response\GenericError(),
    ]
)]
final readonly class PutOrderAction implements SingleActionInterface
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

        if (
            PlaylistSources::Songs !== $record->source
            || PlaylistOrders::Sequential !== $record->order
        ) {
            throw new Exception(__('This playlist is not a sequential playlist.'));
        }

        $order = Types::array($request->getParam('order'));

        $this->spmRepo->setMediaOrder($record, $order);

        // Write changes to file.
        $message = new WritePlaylistFileMessage();
        $message->playlist_id = $record->id;

        $this->messageBus->dispatch($message);

        return $response->withJson($order);
    }
}
