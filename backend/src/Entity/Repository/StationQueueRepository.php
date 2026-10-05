<?php

declare(strict_types=1);

namespace App\Entity\Repository;

use App\Entity\Enums\PlaylistSources;
use App\Entity\Interfaces\SongInterface;
use App\Entity\Station;
use App\Entity\StationMedia;
use App\Entity\StationPlaylist;
use App\Entity\StationPlaylistMedia;
use App\Entity\StationQueue;
use App\Utilities\Time;
use Carbon\CarbonImmutable;
use DateTimeImmutable;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

/**
 * @extends AbstractStationBasedRepository<StationQueue>
 */
final class StationQueueRepository extends AbstractStationBasedRepository
{
    protected string $entityClass = StationQueue::class;

    public function __construct(
        private readonly StationPlaylistRepository $spRepo
    ) {
    }

    public function clearForMediaAndPlaylist(
        StationMedia $media,
        StationPlaylist $playlist
    ): void {
        $this->em->createQuery(
            <<<'DQL'
                DELETE FROM App\Entity\StationQueue sq
                WHERE sq.media = :media
                AND sq.playlist = :playlist
                AND sq.is_played = 0
            DQL
        )->setParameter('media', $media)
            ->setParameter('playlist', $playlist)
            ->execute();
    }

    public function clearForPlaylist(
        StationPlaylist $playlist
    ): void {
        $this->em->createQuery(
            <<<'DQL'
                DELETE FROM App\Entity\StationQueue sq
                WHERE sq.playlist = :playlist
                AND sq.is_played = 0
            DQL
        )->setParameter('playlist', $playlist)
            ->execute();
    }

    public function getNextVisible(Station $station): ?StationQueue
    {
        return $this->getUnplayedBaseQuery($station)
            ->andWhere('sq.is_visible = 1')
            ->getQuery()
            ->setMaxResults(1)
            ->getOneOrNullResult();
    }

    public function trackPlayed(
        Station $station,
        StationQueue $row
    ): void {
        $this->em->createQuery(
            <<<'DQL'
            UPDATE App\Entity\StationQueue sq
            SET sq.timestamp_played = :timestamp
            WHERE sq.station = :station
            AND sq.id = :id
            DQL
        )->setParameter('timestamp', Time::nowUtc())
            ->setParameter('station', $station)
            ->setParameter('id', $row->id)
            ->execute();

        $this->em->createQuery(
            <<<'DQL'
            UPDATE App\Entity\StationQueue sq
            SET sq.is_played=1, sq.sent_to_autodj=1
            WHERE sq.station = :station
            AND sq.is_played = 0
            AND (sq.id = :id OR sq.timestamp_cued < :cued)
        DQL
        )->setParameter('station', $station)
            ->setParameter('id', $row->id)
            ->setParameter('cued', $row->timestamp_cued)
            ->execute();
    }

    public function isPlaylistRecentlyPlayed(
        StationPlaylist $playlist,
        ?int $playPerSongs = null
    ): bool {
        $playPerSongs ??= $playlist->play_per_songs;

        $playlistIds = $this->spRepo->getPlaylistAndNestedMemberIds($playlist);

        $recentPlayedQuery = $this->em->createQuery(
            <<<'DQL'
                SELECT IDENTITY(sq.playlist) AS playlist_id
                FROM App\Entity\StationQueue sq
                WHERE sq.station = :station
                AND (IDENTITY(sq.playlist) IN (:playlistIds) OR sq.is_visible = 1)
                ORDER BY sq.id DESC
            DQL
        )->setParameters([
            'station' => $playlist->station,
            'playlistIds' => $playlistIds,
        ])->setMaxResults($playPerSongs);

        $recentPlayedPlaylistIds = array_map(
            'intval',
            $recentPlayedQuery->getSingleColumnResult()
        );

        return array_intersect($playlistIds, $recentPlayedPlaylistIds) !== [];
    }

    /**
     * @return mixed[]
     */
    public function getRecentlyPlayedByTimeRange(
        Station $station,
        DateTimeImmutable $now,
        int $minutes
    ): array {
        $threshold = CarbonImmutable::instance($now)->subMinutes($minutes);

        return $this->em->createQuery(
            <<<'DQL'
                SELECT sq.song_id, sq.timestamp_played, sq.title, sq.artist
                FROM App\Entity\StationQueue sq
                WHERE sq.station = :station
                AND (sq.is_played = 0 OR sq.timestamp_played >= :threshold)
                ORDER BY sq.timestamp_played DESC
            DQL
        )->setParameter('station', $station)
            ->setParameter('threshold', $threshold)
            ->getArrayResult();
    }

    /**
     * @param Station $station
     * @return StationQueue[]
     */
    public function getUnplayedQueue(Station $station): array
    {
        return $this->getUnplayedBaseQuery($station)->getQuery()->execute();
    }

    public function clearUpcomingQueue(Station $station): void
    {
        $this->em->getConnection()->transactional(function () use ($station): void {
            // Run before delete to restore rows that will be removed
            $this->restorePlaylistQueueSlots($station, true);

            $this->em->createQuery(
                <<<'DQL'
                    DELETE FROM App\Entity\StationQueue sq
                    WHERE sq.station = :station
                    AND sq.sent_to_autodj = 0
                DQL
            )->setParameter('station', $station)
                ->execute();
        });
    }

    public function getNextToSendToAutoDj(Station $station): ?StationQueue
    {
        return $this->getBaseQuery($station)
            ->andWhere('sq.sent_to_autodj = 0')
            ->orderBy('sq.timestamp_cued', 'ASC')
            ->getQuery()
            ->setMaxResults(1)
            ->getOneOrNullResult();
    }

    public function findRecentlyCuedSong(
        Station $station,
        SongInterface $song
    ): ?StationQueue {
        return $this->getUnplayedBaseQuery($station)
            ->andWhere('sq.sent_to_autodj = 1')
            ->andWhere('sq.song_id = :song_id')
            ->setParameter('song_id', $song->song_id)
            ->getQuery()
            ->setMaxResults(1)
            ->getOneOrNullResult();
    }

    public function hasCuedPlaylistMedia(StationPlaylist $playlist): bool
    {
        $station = $playlist->station;

        $cuedPlaylistContentCountQuery = $this->getUnplayedBaseQuery($station)
            ->select('count(sq.id)')
            ->andWhere('sq.playlist = :playlist')
            ->setParameter('playlist', $playlist)
            ->getQuery();

        $cuedPlaylistContentCount = $cuedPlaylistContentCountQuery->getSingleScalarResult();
        return $cuedPlaylistContentCount > 0;
    }

    public function hasCuedPlaylistGroupMedia(StationPlaylist $playlist): bool
    {
        if ($playlist->playlists->count() === 0) {
            return false;
        }

        foreach ($playlist->playlists as $childPlaylist) {
            $hasChildPlaylistCuedMedia = match ($childPlaylist->playlist->source) {
                PlaylistSources::Playlists => $this->hasCuedPlaylistGroupMedia($childPlaylist->playlist),
                PlaylistSources::Songs => $this->hasCuedPlaylistMedia($childPlaylist->playlist),
                default => false
            };

            if ($hasChildPlaylistCuedMedia) {
                return true;
            }
        }

        return false;
    }

    public function getUnplayedBaseQuery(Station $station): QueryBuilder
    {
        return $this->getBaseQuery($station)
            ->andWhere('sq.is_played = 0')
            ->orderBy('sq.sent_to_autodj', 'DESC')
            ->addOrderBy('sq.timestamp_cued', 'ASC');
    }

    private function getBaseQuery(Station $station): QueryBuilder
    {
        return $this->em->createQueryBuilder()
            ->select('sq, sm, sp')
            ->from(StationQueue::class, 'sq')
            ->leftJoin('sq.media', 'sm')
            ->leftJoin('sq.playlist', 'sp')
            ->where('sq.station = :station')
            ->setParameter('station', $station);
    }

    public function clearUnplayed(?Station $station = null): void
    {
        $this->em->getConnection()->transactional(function () use ($station): void {
            // Must run before the delete, it reads the rows being removed.
            $this->restorePlaylistQueueSlots($station, false);

            $clearUnplayedQueueBuilder = $this->em->createQueryBuilder()
                ->delete(StationQueue::class, 'sq')
                ->where('sq.is_played = 0');

            if ($station !==  null) {
                $clearUnplayedQueueBuilder->andWhere('sq.station = :station')
                    ->setParameter('station', $station);
            }

            $clearUnplayedQueueBuilder->getQuery()->execute();
        });
    }

    /**
     * Re-queue the playlist media of unplayed queue rows so that discarding them
     * does not skip the tracks in the playlist rotation.
     *
     * Playlist group slots are not restored, queue rows only store the group chain by name.
     */
    private function restorePlaylistQueueSlots(?Station $station, bool $onlyUnsentToAutoDj): void
    {
        $restoreSlotsQueryBuilder = $this->em->createQueryBuilder()
            ->update(StationPlaylistMedia::class, 'spm')
            ->set('spm.is_queued', 1);

        $queuedUnplayedMediaQueryBuilder = $this->em->createQueryBuilder()
            ->select('spm2.id')
            ->from(StationPlaylistMedia::class, 'spm2')
            ->join(
                join: StationQueue::class,
                alias: 'sq',
                conditionType: Join::WITH,
                condition: 'sq.media = spm2.media AND sq.playlist = spm2.playlist'
            )
            ->where('sq.is_played = 0');

        if ($station !== null) {
            $queuedUnplayedMediaQueryBuilder->andWhere('sq.station = :station');
            $restoreSlotsQueryBuilder->setParameter('station', $station);
        }

        if ($onlyUnsentToAutoDj) {
            $queuedUnplayedMediaQueryBuilder->andWhere('sq.sent_to_autodj = 0');
        }

        $restoreSlotsQueryBuilder->where(
            $restoreSlotsQueryBuilder->expr()->in('spm.id', $queuedUnplayedMediaQueryBuilder->getDQL())
        );

        $restoreSlotsQueryBuilder->getQuery()->execute();

        $this->resyncManagedEntities(
            StationPlaylistMedia::class,
            static fn(StationPlaylistMedia $spm): bool => !$spm->is_queued
        );
    }

    public function cleanup(int $daysToKeep): void
    {
        $threshold = Time::nowUtc()->subDays($daysToKeep);

        $this->em->createQuery(
            <<<'DQL'
                DELETE FROM App\Entity\StationQueue sq
                WHERE sq.timestamp_cued <= :threshold
            DQL
        )->setParameter('threshold', $threshold)
            ->execute();
    }
}
