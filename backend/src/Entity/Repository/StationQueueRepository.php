<?php

declare(strict_types=1);

namespace App\Entity\Repository;

use App\Entity\Interfaces\SongInterface;
use App\Entity\Station;
use App\Entity\StationMedia;
use App\Entity\StationPlaylist;
use App\Entity\StationQueue;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Doctrine\ORM\QueryBuilder;

/**
 * @extends AbstractStationBasedRepository<StationQueue>
 */
final class StationQueueRepository extends AbstractStationBasedRepository
{
    protected string $entityClass = StationQueue::class;

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
        )->setParameter('timestamp', time())
            ->setParameter('station', $station)
            ->setParameter('id', $row->getIdRequired())
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
            ->setParameter('id', $row->getIdRequired())
            ->setParameter('cued', $row->getTimestampCued())
            ->execute();
    }

    public function isPlaylistRecentlyPlayed(
        StationPlaylist $playlist,
        ?int $playPerSongs = null
    ): bool {
        $playPerSongs ??= $playlist->getPlayPerSongs();

        $recentPlayedQuery = $this->em->createQuery(
            <<<'DQL'
                SELECT sq.playlist_id
                FROM App\Entity\StationQueue sq
                WHERE sq.station = :station
                AND sq.playlist_id IS NOT NULL
                AND (sq.playlist = :playlist OR sq.is_visible = 1)
                ORDER BY sq.id DESC
            DQL
        )->setParameters([
            'station' => $playlist->getStation(),
            'playlist' => $playlist,
        ])->setMaxResults($playPerSongs);

        $recentPlayedPlaylists = $recentPlayedQuery->getSingleColumnResult();
        return in_array($playlist->getIdRequired(), (array)$recentPlayedPlaylists, true);
    }

    /**
     * @return mixed[]
     */
    public function getRecentlyPlayedByTimeRange(
        Station $station,
        CarbonInterface $now,
        int $minutes
    ): array {
        $threshold = $now->subMinutes($minutes)->getTimestamp();

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
        $this->em->createQuery(
            <<<'DQL'
                DELETE FROM App\Entity\StationQueue sq
                WHERE sq.station = :station
                AND sq.sent_to_autodj = 0
            DQL
        )->setParameter('station', $station)
            ->execute();
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
            ->setParameter('song_id', $song->getSongId())
            ->getQuery()
            ->setMaxResults(1)
            ->getOneOrNullResult();
    }

    public function hasCuedPlaylistMedia(StationPlaylist $playlist): bool
    {
        $station = $playlist->getStation();

        $cuedPlaylistContentCountQuery = $this->getUnplayedBaseQuery($station)
            ->select('count(sq.id)')
            ->andWhere('sq.playlist = :playlist')
            ->setParameter('playlist', $playlist)
            ->getQuery();

        $cuedPlaylistContentCount = $cuedPlaylistContentCountQuery->getSingleScalarResult();
        return $cuedPlaylistContentCount > 0;
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
        $qb = $this->em->createQueryBuilder()
            ->delete(StationQueue::class, 'sq')
            ->where('sq.is_played = 0');

        if (null !== $station) {
            $qb->andWhere('sq.station = :station')
                ->setParameter('station', $station);
        }

        $qb->getQuery()->execute();
    }

    public function cleanup(int $daysToKeep): void
    {
        $threshold = CarbonImmutable::now()
            ->subDays($daysToKeep)
            ->getTimestamp();

        $this->em->createQuery(
            <<<'DQL'
                DELETE FROM App\Entity\StationQueue sq
                WHERE sq.timestamp_cued <= :threshold
            DQL
        )->setParameter('threshold', $threshold)
            ->execute();
    }
}
