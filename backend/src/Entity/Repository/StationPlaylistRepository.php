<?php

declare(strict_types=1);

namespace App\Entity\Repository;

use App\Entity\Enums\PlaylistOrders;
use App\Entity\Enums\PlaylistSources;
use App\Entity\Station;
use App\Entity\StationPlaylist;
use App\Utilities\Time;
use Carbon\CarbonImmutable;
use Doctrine\ORM\QueryBuilder;
use InvalidArgumentException;

/**
 * @extends AbstractStationBasedRepository<StationPlaylist>
 */
final class StationPlaylistRepository extends AbstractStationBasedRepository
{
    protected string $entityClass = StationPlaylist::class;

    /**
     * @return StationPlaylist[]
     */
    public function getAllForStation(Station $station): array
    {
        return $this->repository->findBy([
            'station' => $station,
        ]);
    }

    public function stationHasActivePlaylists(Station $station): bool
    {
        foreach ($station->playlists as $playlist) {
            if (!$playlist->is_enabled) {
                continue;
            }

            if (PlaylistSources::RemoteUrl === $playlist->source) {
                return true;
            }

            $mediaCount = $this->em->createQuery(
                <<<DQL
                    SELECT COUNT(spm.id) FROM App\Entity\StationPlaylistMedia spm
                    JOIN spm.playlist sp
                    WHERE sp.station = :station
                DQL
            )->setParameter('station', $station)
                ->getSingleScalarResult();

            if ($mediaCount > 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param StationPlaylist $playlist A playlist that is holding other playlists inside
     *
     * @return StationPlaylist[]
     */
    public function getPlaylistGroupQueue(StationPlaylist $playlist): array
    {
        if (PlaylistSources::Playlists !== $playlist->source) {
            throw new InvalidArgumentException('Playlist must contain playlists.');
        }

        $queuedPlaylistQuery = $this->em->createQueryBuilder()
            ->select('sp')
            ->from(StationPlaylist::class, 'sp')
            ->join('sp.playlist_groups', 'spg')
            ->where('spg.playlist_group = :playlistGroup')
            ->setParameter('playlistGroup', $playlist);

        if (PlaylistOrders::Random === $playlist->order) {
            $queuedPlaylistQuery = $queuedPlaylistQuery->orderBy('RAND()');
        } else {
            $queuedPlaylistQuery = $queuedPlaylistQuery->andWhere('spg.is_queued = 1')
                ->orderBy('spg.weight', 'ASC');
        }

        return $queuedPlaylistQuery->getQuery()->execute();
    }

    public function isPlaylistGroupQueueCompletelyFilled(StationPlaylist $playlist): bool
    {
        if (PlaylistSources::Playlists !== $playlist->source) {
            throw new InvalidArgumentException('Playlist must contain playlists.');
        }

        if (PlaylistOrders::Random === $playlist->order) {
            return true;
        }

        $notQueuedPlaylistCount = $this->getCountPlaylistGroupBaseQuery($playlist)
            ->andWhere('spg.is_queued = 0')
            ->getQuery()
            ->getSingleScalarResult();

        return $notQueuedPlaylistCount === 0;
    }

    public function isPlaylistGroupQueueEmpty(StationPlaylist $playlist): bool
    {
        if (PlaylistSources::Songs !== $playlist->source) {
            return false;
        }

        if (PlaylistOrders::Random === $playlist->order) {
            return false;
        }

        $totalPlaylistCount = $this->getCountPlaylistGroupBaseQuery($playlist)
            ->getQuery()
            ->getSingleScalarResult();

        $notQueuedPlaylistCount = $this->getCountPlaylistGroupBaseQuery($playlist)
            ->andWhere('spg.is_queued = 0')
            ->getQuery()
            ->getSingleScalarResult();

        return $notQueuedPlaylistCount === $totalPlaylistCount;
    }

    private function getCountPlaylistGroupBaseQuery(StationPlaylist $playlist): QueryBuilder
    {
        return $this->em->createQueryBuilder()
            ->select('count(spg.id)')
            ->from(StationPlaylist::class, 'sp')
            ->join('sp.playlist_groups', 'spg')
            ->where('spg.playlist_group = :playlistGroup')
            ->setParameter('playlistGroup', $playlist);
    }

    /**
     * @param StationPlaylist $playlist A playlist that is holding other playlists inside
     */
    public function resetPlaylistGroupQueue(
        StationPlaylist $playlist,
        ?CarbonImmutable $now = null
    ): void {
        if (PlaylistSources::Playlists !== $playlist->source) {
            throw new InvalidArgumentException('Playlist must contain playlists.');
        }

        if (PlaylistOrders::Sequential === $playlist->order) {
            $this->em->createQuery(
                <<<'DQL'
                    UPDATE App\Entity\StationPlaylistGroup spg
                    SET spg.is_queued = 1
                    WHERE spg.playlist_group = :playlistGroup
                DQL
            )->setParameter('playlistGroup', $playlist)
                ->execute();
        } elseif (PlaylistOrders::Shuffle === $playlist->order) {
            $this->em->wrapInTransaction(
                function () use ($playlist): void {
                    $allSpgRecordsQuery = $this->em->createQuery(
                        <<<'DQL'
                            SELECT spg.id
                            FROM App\Entity\StationPlaylistGroup spg
                            WHERE spg.playlist_group = :playlistGroup
                            ORDER BY RAND()
                        DQL
                    )->setParameter('playlistGroup', $playlist);

                    $updateSpgWeightQuery = $this->em->createQuery(
                        <<<'DQL'
                            UPDATE App\Entity\StationPlaylistGroup spg
                            SET spg.weight = :weight, spg.is_queued = 1
                            WHERE spg.id = :id
                        DQL
                    );

                    $allSpgRecords = $allSpgRecordsQuery->toIterable([], $allSpgRecordsQuery::HYDRATE_SCALAR);
                    $weight = 1;

                    foreach ($allSpgRecords as $spgId) {
                        $updateSpgWeightQuery->setParameter('id', $spgId)
                            ->setParameter('weight', $weight)
                            ->execute();

                        $weight++;
                    }
                }
            );
        }

        $now ??= Time::nowUtc();

        $playlist->queue_reset_at = $now;
        $this->em->persist($playlist);
        $this->em->flush();
    }
}
