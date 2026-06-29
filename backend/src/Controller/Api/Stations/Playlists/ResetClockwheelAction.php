<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Playlists;

use App\Container\EntityManagerAwareTrait;
use App\Controller\SingleActionInterface;
use App\Entity\Api\Status;
use App\Entity\Enums\PlaylistTypes;
use App\Entity\Repository\StationPlaylistRepository;
use App\Exception;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[OA\Put(
    path: '/station/{station_id}/playlist/{id}/reset-clockwheel',
    operationId: 'putResetPlaylistClockwheel',
    summary: 'Reset the clockwheel state of a clockwheel playlist back to step 0.',
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
final class ResetClockwheelAction implements SingleActionInterface
{
    use EntityManagerAwareTrait;

    public function __construct(
        private readonly StationPlaylistRepository $playlistRepo,
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
        $record = $this->playlistRepo->requireForStation($id, $station);

        if (PlaylistTypes::Clockwheel !== $record->type) {
            throw new Exception(__('This playlist is not a clockwheel playlist.'));
        }

        $record->clockwheel_step = 0;
        $record->clockwheel_songs_played = 0;
        $this->em->persist($record);
        $this->em->flush();

        return $response->withJson(
            new Status(true, __('Clockwheel reset to beginning.'))
        );
    }
}
