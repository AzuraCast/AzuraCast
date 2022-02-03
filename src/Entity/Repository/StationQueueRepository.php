<?php

declare(strict_types=1);

namespace App\Entity\Repository;

use App\Doctrine\Repository;
use App\Entity;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;

/**
 * @extends Repository<Entity\StationQueue>
 */
class StationQueueRepository extends Repository
{
    public function clearForMediaAndPlaylist(Entity\StationMedia $media, Entity\StationPlaylist $playlist): void
    {
        $this->em->createQuery(
            <<<'DQL'
                DELETE FROM App\Entity\StationQueue sq
                WHERE sq.media = :media AND sq.playlist = :playlist
            DQL
        )
            ->setParameter('media', $media)
            ->setParameter('playlist', $playlist)
            ->execute();
    }

    public function getNextVisible(Entity\Station $station): ?Entity\StationQueue
    {
        return $this->getUnplayedBaseQuery($station)
            ->andWhere('sq.is_visible = 1')
            ->getQuery()
            ->setMaxResults(1)
            ->getOneOrNullResult();
    }

    public function trackPlayed(
        Entity\Station $station,
        Entity\StationQueue $row
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

    /**
     * @return int[]
     */
    public function getRecentPlaylists(
        Entity\Station $station,
        int $rows
    ): array {
        return $this->em->createQuery(
            <<<'DQL'
                SELECT sq.timestamp_played, sq.playlist_id
                FROM App\Entity\StationQueue sq
                WHERE sq.station = :station
                ORDER BY sq.sent_to_autodj ASC, sq.timestamp_played DESC
            DQL
        )->setParameter('station', $station)
            ->setMaxResults($rows)
            ->getArrayResult();
    }

    /**
     * @return mixed[]
     */
    public function getRecentlyPlayedByTimeRange(
        Entity\Station $station,
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
     * @param Entity\Station $station
     * @return Entity\StationQueue[]
     */
    public function getUnplayedQueue(Entity\Station $station): array
    {
        return $this->getUnplayedQuery($station)->execute();
    }

    public function getUnplayedQuery(Entity\Station $station): Query
    {
        return $this->getUnplayedBaseQuery($station)->getQuery();
    }

    public function getLatestVisibleRow(Entity\Station $station): ?Entity\StationQueue
    {
        return $this->getRecentBaseQuery($station)
            ->andWhere('sq.sent_to_autodj = 1')
            ->andWhere('sq.is_visible = 1')
            ->getQuery()
            ->setMaxResults(1)
            ->getOneOrNullResult();
    }

    public function clearUpcomingQueue(Entity\Station $station): void
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

    public function getNextToSendToAutoDj(Entity\Station $station): ?Entity\StationQueue
    {
        return $this->getBaseQuery($station)
            ->andWhere('sq.sent_to_autodj = 0')
            ->orderBy('sq.timestamp_cued', 'ASC')
            ->getQuery()
            ->setMaxResults(1)
            ->getOneOrNullResult();
    }

    public function findRecentlyCuedSong(
        Entity\Station $station,
        Entity\Interfaces\SongInterface $song
    ): ?Entity\StationQueue {
        return $this->getUnplayedBaseQuery($station)
            ->andWhere('sq.sent_to_autodj = 1')
            ->andWhere('sq.song_id = :song_id')
            ->setParameter('song_id', $song->getSongId())
            ->getQuery()
            ->setMaxResults(1)
            ->getOneOrNullResult();
    }

    public function hasCuedPlaylistMedia(Entity\StationPlaylist $playlist): bool
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

    protected function getRecentBaseQuery(Entity\Station $station): QueryBuilder
    {
        return $this->getBaseQuery($station)
            ->orderBy('sq.timestamp_cued', 'DESC');
    }

    protected function getUnplayedBaseQuery(Entity\Station $station): QueryBuilder
    {
        return $this->getBaseQuery($station)
            ->andWhere('sq.is_played = 0')
            ->orderBy('sq.sent_to_autodj', 'DESC')
            ->addOrderBy('sq.timestamp_cued', 'ASC');
    }

    protected function getBaseQuery(Entity\Station $station): QueryBuilder
    {
        return $this->em->createQueryBuilder()
            ->select('sq, sm, sp')
            ->from(Entity\StationQueue::class, 'sq')
            ->leftJoin('sq.media', 'sm')
            ->leftJoin('sq.playlist', 'sp')
            ->where('sq.station = :station')
            ->setParameter('station', $station);
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
