<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Playlists;

use App\Container\EntityManagerAwareTrait;
use App\Controller\SingleActionInterface;
use App\Entity\Api\Status;
use App\Entity\Enums\PlaylistTypes;
use App\Entity\Repository\StationPlaylistRepository;
use App\Entity\StationPlaylist;
use App\Entity\StationPlaylistChild;
use App\Exception;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use App\Utilities\Types;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[OA\Put(
    path: '/station/{station_id}/playlist/{id}/children',
    operationId: 'putStationPlaylistChildren',
    summary: 'Set the child playlists of a clockwheel playlist (full replace).',
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
final class PutChildrenAction implements SingleActionInterface
{
    use EntityManagerAwareTrait;

    private const int MAX_NESTING_DEPTH = 5;

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

        $children = Types::array($request->getParsedBody());

        $childPlaylistIds = [];
        foreach ($children as $child) {
            $childId = (int)($child['child_playlist_id'] ?? 0);
            if ($childId > 0) {
                $childPlaylistIds[] = $childId;
            }
        }

        if (in_array($record->id, $childPlaylistIds, true)) {
            throw new Exception(__('A clockwheel playlist cannot contain itself.'));
        }

        foreach ($childPlaylistIds as $childId) {
            $childPlaylist = $this->playlistRepo->findForStation($childId, $station);
            if (null === $childPlaylist) {
                throw new Exception(__('Child playlist not found: %d', $childId));
            }

            if (!in_array($childPlaylist->type, [PlaylistTypes::Standard, PlaylistTypes::Clockwheel], true)) {
                throw new Exception(
                    __(
                        'Only General Rotation playlists can be used as clockwheel children: %s',
                        $childPlaylist->name
                    )
                );
            }

            if (PlaylistTypes::Clockwheel === $childPlaylist->type) {
                $this->checkCircularReference($record, $childPlaylist, 1);
            }
        }

        $this->em->createQuery(
            <<<'DQL'
                DELETE App\Entity\StationPlaylistChild spc
                WHERE IDENTITY(spc.parentPlaylist) = :playlist_id
            DQL
        )->setParameter('playlist_id', $record->id)
            ->execute();

        $position = 0;
        foreach ($children as $child) {
            $childId = (int)($child['child_playlist_id'] ?? 0);
            $songCount = max(1, (int)($child['song_count'] ?? 1));
            $allowRequests = (bool)($child['allow_requests'] ?? false);

            $childPlaylist = ($childId > 0)
                ? $this->playlistRepo->findForStation($childId, $station)
                : null;

            $childEntity = new StationPlaylistChild(
                $record,
                $childPlaylist,
                $position,
                $songCount,
                $allowRequests
            );

            $this->em->persist($childEntity);
            $position++;
        }

        $record->clockwheel_step = 0;
        $record->clockwheel_songs_played = 0;
        $this->em->persist($record);

        $this->em->flush();

        return $response->withJson(new Status(true, __('Clockwheel children updated.')));
    }

    /**
     * Recursively check for circular references in clockwheel playlists.
     */
    private function checkCircularReference(
        StationPlaylist $ancestor,
        StationPlaylist $current,
        int $depth
    ): void {
        if ($depth > self::MAX_NESTING_DEPTH) {
            throw new Exception(
                __('Maximum clockwheel nesting depth of %d exceeded.', self::MAX_NESTING_DEPTH)
            );
        }

        foreach ($current->child_items as $child) {
            $childPlaylist = $child->childPlaylist;
            if (null === $childPlaylist) {
                continue;
            }

            if ($childPlaylist->id === $ancestor->id) {
                throw new Exception(
                    __('Circular reference detected: playlist "%s" would create a loop.', $current->name)
                );
            }

            if (PlaylistTypes::Clockwheel === $childPlaylist->type) {
                $this->checkCircularReference($ancestor, $childPlaylist, $depth + 1);
            }
        }
    }
}
