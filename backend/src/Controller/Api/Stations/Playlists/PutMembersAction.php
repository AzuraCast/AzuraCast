<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Playlists;

use App\Controller\SingleActionInterface;
use App\Entity\Enums\PlaylistSources;
use App\Entity\Repository\StationPlaylistRepository;
use App\Entity\StationPlaylist;
use App\Entity\StationPlaylistGroup;
use App\Exception;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use App\Utilities\Types;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[OA\Put(
    path: '/station/{station_id}/playlist/{id}/members',
    operationId: 'putStationPlaylistMembers',
    summary: 'Set the member playlists of the specified playlist group.',
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
final readonly class PutMembersAction implements SingleActionInterface
{
    public function __construct(
        private StationPlaylistRepository $playlistRepo,
        private EntityManagerInterface $entityManager,
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

        if (PlaylistSources::Playlists !== $record->source) {
            throw new Exception(__('This playlist is not a playlist group.'));
        }

        /** @var array<array{id?: mixed, weight?: mixed}> $members */
        $members = Types::array($request->getParam('members'));

        // Validate all members before making any changes to ensure we don't try
        // to create any broken/impossible relations and abort early.
        foreach ($members as $member) {
            $memberId = Types::int($member['id'] ?? null);

            if ($memberId === $record->id) {
                throw new Exception(__('A playlist group cannot contain itself.'));
            }

            $memberPlaylist = $this->playlistRepo->findForStation($memberId, $station);
            if (!$memberPlaylist instanceof StationPlaylist) {
                throw new Exception(
                    sprintf(__('Playlist %d does not belong to this station.'), $memberId)
                );
            }

            if ($this->wouldCreateCircularReference($record, $memberPlaylist)) {
                throw new Exception(
                    sprintf(
                        __('Adding playlist "%s" would create a circular reference.'),
                        $memberPlaylist->name
                    )
                );
            }
        }

        // Since we allow having a playlist multiple times as a member of a group
        // we need to recreate all memberships of the playlist group instead of updating entries.
        // Not the most elegant solution but I didn't want to overcomplicate this for the first version.
        $this->entityManager->createQuery(
            <<<'DQL'
                DELETE FROM App\Entity\StationPlaylistGroup spg
                WHERE spg.playlist_group = :playlistGroup
            DQL
        )->setParameter('playlistGroup', $record)
            ->execute();

        foreach ($members as $member) {
            $memberId = Types::int($member['id'] ?? null);
            $weight = Types::int($member['weight'] ?? 0);

            $memberPlaylist = $this->playlistRepo->findForStation($memberId, $station);
            if (!$memberPlaylist instanceof StationPlaylist) {
                continue;
            }

            $stationPlaylistGroup = new StationPlaylistGroup($memberPlaylist, $record);
            $stationPlaylistGroup->weight = $weight;
            $this->entityManager->persist($stationPlaylistGroup);
        }

        $this->entityManager->flush();

        return $response->withJson(['success' => true]);
    }

    /**
     * Check whether adding $candidate as a member of $group would create a circular reference.
     * A circular reference exists if $group already appears (directly or transitively) as a
     * member of $candidate.
     */
    private function wouldCreateCircularReference(
        StationPlaylist $group,
        StationPlaylist $candidate
    ): bool {
        if (PlaylistSources::Playlists !== $candidate->source) {
            return false;
        }

        foreach ($candidate->playlists as $stationPlaylistGroup) {
            $childPlaylist = $stationPlaylistGroup->playlist;

            if ($childPlaylist->id === $group->id) {
                return true;
            }

            if ($this->wouldCreateCircularReference($group, $childPlaylist)) {
                return true;
            }
        }

        return false;
    }
}
