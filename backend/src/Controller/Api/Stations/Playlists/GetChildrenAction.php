<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Playlists;

use App\Container\EntityManagerAwareTrait;
use App\Controller\SingleActionInterface;
use App\Entity\Enums\PlaylistTypes;
use App\Entity\Repository\StationPlaylistRepository;
use App\Exception;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[OA\Get(
    path: '/station/{station_id}/playlist/{id}/children',
    operationId: 'getStationPlaylistChildren',
    summary: 'Get the child playlists of a clockwheel playlist, in order.',
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
final class GetChildrenAction implements SingleActionInterface
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

        $children = $this->em->createQuery(
            <<<'DQL'
                SELECT spc, sp
                FROM App\Entity\StationPlaylistChild spc
                LEFT JOIN spc.childPlaylist sp
                WHERE IDENTITY(spc.parentPlaylist) = :playlist_id
                ORDER BY spc.position ASC
            DQL
        )->setParameter('playlist_id', $id)
            ->getResult();

        $result = [];
        foreach ($children as $child) {
            $result[] = [
                'id' => $child->id,
                'child_playlist_id' => $child->childPlaylist?->id,
                'child_playlist_name' => $child->childPlaylist?->name,
                'position' => $child->position,
                'song_count' => $child->song_count,
                'allow_requests' => $child->allow_requests,
            ];
        }

        return $response->withJson($result);
    }
}
