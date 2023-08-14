<?php

declare(strict_types=1);

namespace App\Entity\Repository;

use App\Doctrine\Repository;
use App\Entity\Api\StationPlaylistQueue;
use App\Entity\Enums\PlaylistOrders;
use App\Entity\Enums\PlaylistSources;
use App\Entity\Station;
use App\Entity\StationMedia;
use App\Entity\StationPlaylist;
use App\Entity\StationPlaylistMedia;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use InvalidArgumentException;
use RuntimeException;

/**
 * @extends Repository<StationPlaylistMedia>
 */
final class StationPlaylistMediaRepository extends Repository
{
    protected string $entityClass = StationPlaylistMedia::class;

    public function __construct(
        private readonly StationQueueRepository $queueRepo
    ) {
    }

    /**
     * @param StationMedia $media
     * @param array<int, int> $playlists Playlists with weight as value (id => weight)
     * @return array<int, int> Affected playlist IDs (id => id)
     */
    public function setPlaylistsForMedia(
        StationMedia $media,
        Station $station,
        array $playlists
    ): array {
        $toDelete = [];

        foreach ($this->getPlaylistsForMedia($media, $station) as $playlistId) {
            if (isset($playlists[$playlistId])) {
                unset($playlists[$playlistId]);
            } else {
                $toDelete[$playlistId] = $playlistId;
            }
        }

        if (0 !== count($toDelete)) {
            $this->em->createQuery(
                <<<'DQL'
                DELETE FROM App\Entity\StationPlaylistMedia spm
                WHERE spm.media = :media
                AND spm.playlist_id IN (:playlistIds)
                DQL
            )->setParameter('media', $media)
                ->setParameter('playlistIds', $toDelete)
                ->execute();
        }

        $added = [];

        foreach ($playlists as $playlistId => $weight) {
            $playlist = $this->em->find(StationPlaylist::class, $playlistId);
            if (!($playlist instanceof StationPlaylist)) {
                continue;
            }

            if (0 === $weight) {
                $weight = $this->getHighestSongWeight($playlist) + 1;
            }
            if (PlaylistOrders::Sequential !== $playlist->getOrder()) {
                $weight = random_int(1, $weight);
            }

            $record = new StationPlaylistMedia($playlist, $media);
            $record->setWeight($weight);
            $this->em->persist($record);

            $added[$playlistId] = $playlistId;
        }

        $this->em->flush();

        return $toDelete + $added;
    }

    /**
     * @param StationMedia $media
     * @param Station $station
     * @return array<array-key, int>
     */
    public function getPlaylistsForMedia(
        StationMedia $media,
        Station $station
    ): array {
        return $this->em->createQuery(
            <<<'DQL'
                SELECT sp.id
                FROM App\Entity\StationPlaylistMedia spm
                LEFT JOIN spm.playlist sp
                WHERE spm.media = :media
                AND sp.station = :station
            DQL
        )->setParameter('media', $media)
            ->setParameter('station', $station)
            ->getSingleColumnResult();
    }

    /**
     * Add the specified media to the specified playlist.
     * Must flush the EntityManager after using.
     *
     * @param StationMedia $media
     * @param StationPlaylist $playlist
     * @param int $weight
     *
     * @return int The weight assigned to the newly added record.
     */
    public function addMediaToPlaylist(
        StationMedia $media,
        StationPlaylist $playlist,
        int $weight = 0
    ): int {
        if (PlaylistSources::Songs !== $playlist->getSource()) {
            throw new RuntimeException('This playlist is not meant to contain songs!');
        }

        // Only update existing record for random-order playlists.
        $isNonSequential = PlaylistOrders::Sequential !== $playlist->getOrder();

        $record = ($isNonSequential)
            ? $this->repository->findOneBy(
                [
                    'media_id' => $media->getId(),
                    'playlist_id' => $playlist->getId(),
                ]
            ) : null;

        if ($record instanceof StationPlaylistMedia) {
            if (0 !== $weight) {
                $record->setWeight($weight);
                $this->em->persist($record);
            }
        } else {
            if (0 === $weight) {
                $weight = $this->getHighestSongWeight($playlist) + 1;
            }
            if ($isNonSequential) {
                $weight = random_int(1, $weight);
            }

            $record = new StationPlaylistMedia($playlist, $media);
            $record->setWeight($weight);
            $this->em->persist($record);
        }

        return $weight;
    }

    public function getHighestSongWeight(StationPlaylist $playlist): int
    {
        try {
            $highestWeight = $this->em->createQuery(
                <<<'DQL'
                    SELECT MAX(e.weight)
                    FROM App\Entity\StationPlaylistMedia e
                    WHERE e.playlist_id = :playlist_id
                DQL
            )->setParameter('playlist_id', $playlist->getId())
                ->getSingleScalarResult();
        } catch (NoResultException) {
            $highestWeight = 1;
        }

        return (int)$highestWeight;
    }

    /**
     * Remove all playlist associations from the specified media object.
     *
     * @param StationMedia $media
     * @param Station|null $station
     *
     * @return array<int, int> Affected Playlist records (id => id)
     */
    public function clearPlaylistsFromMedia(
        StationMedia $media,
        ?Station $station = null
    ): array {
        $affectedPlaylists = [];

        $playlists = $media->getPlaylists();
        if (null !== $station) {
            $playlists = $playlists->filter(
                function (StationPlaylistMedia $spm) use ($station) {
                    return $spm->getPlaylist()->getStation()->getId() === $station->getId();
                }
            );
        }

        foreach ($playlists as $spmRow) {
            $playlist = $spmRow->getPlaylist();
            $affectedPlaylists[$playlist->getIdRequired()] = $playlist->getIdRequired();

            $this->queueRepo->clearForMediaAndPlaylist($media, $playlist);

            $this->em->remove($spmRow);
        }

        return $affectedPlaylists;
    }

    public function emptyPlaylist(
        StationPlaylist $playlist
    ): void {
        $this->em->createQuery(
            <<<'DQL'
                DELETE FROM App\Entity\StationPlaylistMedia spm
                WHERE spm.playlist = :playlist
            DQL
        )->setParameter('playlist', $playlist)
            ->execute();

        $this->queueRepo->clearForPlaylist($playlist);
    }

    /**
     * Set the order of the media, specified as
     * [
     *    media_id => new_weight,
     *    ...
     * ]
     *
     * @param StationPlaylist $playlist
     * @param array $mapping
     */
    public function setMediaOrder(StationPlaylist $playlist, array $mapping): void
    {
        $updateQuery = $this->em->createQuery(
            <<<'DQL'
                UPDATE App\Entity\StationPlaylistMedia e
                SET e.weight = :weight
                WHERE e.playlist_id = :playlist_id
                AND e.id = :id
            DQL
        )->setParameter('playlist_id', $playlist->getId());

        $this->em->wrapInTransaction(
            function () use ($updateQuery, $mapping): void {
                foreach ($mapping as $id => $weight) {
                    $updateQuery->setParameter('id', $id)
                        ->setParameter('weight', $weight)
                        ->execute();
                }
            }
        );
    }

    public function resetQueue(StationPlaylist $playlist, CarbonInterface $now = null): void
    {
        if (PlaylistSources::Songs !== $playlist->getSource()) {
            throw new InvalidArgumentException('Playlist must contain songs.');
        }

        if (PlaylistOrders::Sequential === $playlist->getOrder()) {
            $this->em->createQuery(
                <<<'DQL'
                    UPDATE App\Entity\StationPlaylistMedia spm
                    SET spm.is_queued = 1
                    WHERE spm.playlist = :playlist
                DQL
            )->setParameter('playlist', $playlist)
                ->execute();
        } elseif (PlaylistOrders::Shuffle === $playlist->getOrder()) {
            $this->em->wrapInTransaction(
                function () use ($playlist): void {
                    $allSpmRecordsQuery = $this->em->createQuery(
                        <<<'DQL'
                            SELECT spm.id
                            FROM App\Entity\StationPlaylistMedia spm
                            WHERE spm.playlist = :playlist
                            ORDER BY RAND()
                        DQL
                    )->setParameter('playlist', $playlist);

                    $updateSpmWeightQuery = $this->em->createQuery(
                        <<<'DQL'
                            UPDATE App\Entity\StationPlaylistMedia spm
                            SET spm.weight=:weight, spm.is_queued=1
                            WHERE spm.id = :id
                        DQL
                    );

                    $allSpmRecords = $allSpmRecordsQuery->toIterable([], $allSpmRecordsQuery::HYDRATE_SCALAR);
                    $weight = 1;

                    foreach ($allSpmRecords as $spmId) {
                        $updateSpmWeightQuery->setParameter('id', $spmId)
                            ->setParameter('weight', $weight)
                            ->execute();

                        $weight++;
                    }
                }
            );
        }

        $now = $now ?? CarbonImmutable::now($playlist->getStation()->getTimezoneObject());

        $playlist->setQueueResetAt($now->getTimestamp());
        $this->em->persist($playlist);
        $this->em->flush();
    }

    public function resetAllQueues(Station $station): void
    {
        $now = CarbonImmutable::now($station->getTimezoneObject());

        foreach ($station->getPlaylists() as $playlist) {
            if (PlaylistSources::Songs !== $playlist->getSource()) {
                continue;
            }

            $this->resetQueue($playlist, $now);
        }
    }

    /**
     * @return StationPlaylistQueue[]
     */
    public function getQueue(StationPlaylist $playlist): array
    {
        if (PlaylistSources::Songs !== $playlist->getSource()) {
            throw new InvalidArgumentException('Playlist must contain songs.');
        }

        $queuedMediaQuery = $this->em->createQueryBuilder()
            ->select(['spm.id AS spm_id', 'sm.id', 'sm.song_id', 'sm.artist', 'sm.title'])
            ->from(StationMedia::class, 'sm')
            ->join('sm.playlists', 'spm')
            ->where('spm.playlist = :playlist')
            ->setParameter('playlist', $playlist);

        if (PlaylistOrders::Random === $playlist->getOrder()) {
            $queuedMediaQuery = $queuedMediaQuery->orderBy('RAND()');
        } else {
            $queuedMediaQuery = $queuedMediaQuery->andWhere('spm.is_queued = 1')
                ->orderBy('spm.weight', 'ASC');
        }

        $queuedMedia = $queuedMediaQuery->getQuery()->getArrayResult();

        return array_map(
            static function ($val): StationPlaylistQueue {
                $record = new StationPlaylistQueue();
                $record->spm_id = $val['spm_id'];
                $record->media_id = $val['id'];
                $record->song_id = $val['song_id'];
                $record->artist = $val['artist'] ?? '';
                $record->title = $val['title'] ?? '';

                return $record;
            },
            $queuedMedia
        );
    }

    public function isQueueCompletelyFilled(StationPlaylist $playlist): bool
    {
        if (PlaylistSources::Songs !== $playlist->getSource()) {
            return true;
        }

        if (PlaylistOrders::Random === $playlist->getOrder()) {
            return true;
        }

        $notQueuedMediaCount = $this->getCountPlaylistMediaBaseQuery($playlist)
            ->andWhere('spm.is_queued = 0')
            ->getQuery()
            ->getSingleScalarResult();

        return $notQueuedMediaCount === 0;
    }

    public function isQueueEmpty(StationPlaylist $playlist): bool
    {
        if (PlaylistSources::Songs !== $playlist->getSource()) {
            return false;
        }

        if (PlaylistOrders::Random === $playlist->getOrder()) {
            return false;
        }

        $totalMediaCount = $this->getCountPlaylistMediaBaseQuery($playlist)
            ->getQuery()
            ->getSingleScalarResult();

        $notQueuedMediaCount = $this->getCountPlaylistMediaBaseQuery($playlist)
            ->andWhere('spm.is_queued = 0')
            ->getQuery()
            ->getSingleScalarResult();

        return $notQueuedMediaCount === $totalMediaCount;
    }

    private function getCountPlaylistMediaBaseQuery(StationPlaylist $playlist): QueryBuilder
    {
        return $this->em->createQueryBuilder()
            ->select('count(spm.id)')
            ->from(StationMedia::class, 'sm')
            ->join('sm.playlists', 'spm')
            ->where('spm.playlist = :playlist')
            ->setParameter('playlist', $playlist);
    }
}
