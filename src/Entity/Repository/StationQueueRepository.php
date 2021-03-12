<?php

namespace App\Entity\Repository;

use App\Doctrine\Repository;
use App\Entity;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;

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
        $queue = $this->getUpcomingQueue($station);

        foreach ($queue as $sh) {
            if ($sh->showInApis()) {
                return $sh;
            }
        }

        return null;
    }

    public function newRecordSentToAutoDj(Entity\StationQueue $queueRow): void
    {
        $queueRow->sentToAutoDj();
        $this->em->persist($queueRow);
        $this->em->flush();
    }

    /**
     * @return mixed[]
     */
    public function getRecentlyPlayed(
        Entity\Station $station,
        int $rows
    ): array {
        return $this->getRecentBaseQuery($station)
            ->setMaxResults($rows)
            ->getQuery()
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

        return $this->getRecentBaseQuery($station)
            ->andWhere('sq.timestamp_cued >= :threshold')
            ->setParameter('threshold', $threshold)
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * @param Entity\Station $station
     *
     * @return Entity\StationQueue[]
     */
    public function getUpcomingQueue(Entity\Station $station): array
    {
        return $this->getUpcomingQuery($station)->execute();
    }

    public function getUpcomingQuery(Entity\Station $station): Query
    {
        return $this->getUpcomingBaseQuery($station)->getQuery();
    }

    public function clearDuplicatesInQueue(Entity\Station $station): void
    {
        $upcomingQueue = $this->getUpcomingQueue($station);

        $lastItem = null;
        foreach ($upcomingQueue as $queue) {
            if (null !== $lastItem && $lastItem === $queue->getSongId()) {
                $this->em->remove($queue);
                continue;
            }

            $lastItem = $queue->getSongId();
        }

        $this->em->flush();
    }

    public function getNextInQueue(Entity\Station $station): ?Entity\StationQueue
    {
        return $this->getUpcomingBaseQuery($station)
            ->getQuery()
            ->setMaxResults(1)
            ->getOneOrNullResult();
    }

    public function findRecentlyCuedSong(Entity\Station $station, Entity\SongInterface $song): ?Entity\StationQueue
    {
        return $this->getRecentBaseQuery($station)
            ->andWhere('sq.sent_to_autodj = 1')
            ->andWhere('sq.song_id = :song_id')
            ->setParameter('song_id', $song->getSongId())
            ->getQuery()
            ->setMaxResults(1)
            ->getOneOrNullResult();
    }

    protected function getRecentBaseQuery(Entity\Station $station): QueryBuilder
    {
        return $this->getBaseQuery($station)
            ->orderBy('sq.timestamp_cued', 'DESC');
    }

    protected function getUpcomingBaseQuery(Entity\Station $station): QueryBuilder
    {
        return $this->getBaseQuery($station)
            ->andWhere('sq.sent_to_autodj = 0')
            ->orderBy('sq.timestamp_cued', 'ASC');
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
