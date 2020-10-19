<?php

namespace App\Entity\Repository;

use App\Doctrine\Repository;
use App\Entity;
use Doctrine\ORM\QueryBuilder;

class StationQueueRepository extends Repository
{
    public function getNextVisible(Entity\Station $station): ?Entity\StationQueue
    {
        $queue = $this->getUpcomingQueue($station);

        foreach ($queue as $sh) {
            if (!$sh->isSentToAutoDj() && $sh->showInApis()) {
                return $sh;
            }
        }

        return null;
    }

    public function newRecordSentToAutoDj(Entity\StationQueue $queueRow): void
    {
        if ($queueRow->isSentToAutoDj()) {
            return;
        }

        $station = $queueRow->getStation();

        // Remove all existing records that are marked as "sent to AutoDJ".
        $this->em->createQuery(/** @lang DQL */ 'DELETE FROM App\Entity\StationQueue sq
            WHERE sq.station = :station AND sq.sent_to_autodj = 1')
            ->setParameter('station', $station)
            ->execute();

        $queueRow->sentToAutoDj();
        $this->em->persist($queueRow);
        $this->em->flush();
    }

    /**
     * @param Entity\Station $station
     *
     * @return Entity\StationQueue[]
     */
    public function getUpcomingQueue(Entity\Station $station): array
    {
        return $this->getUpcomingBaseQuery($station)
            ->andWhere('sq.sent_to_autodj = 0')
            ->getQuery()
            ->execute();
    }

    public function getNextInQueue(Entity\Station $station): ?Entity\StationQueue
    {
        return $this->getUpcomingBaseQuery($station)
            ->andWhere('sq.sent_to_autodj = 0')
            ->getQuery()
            ->setMaxResults(1)
            ->getOneOrNullResult();
    }

    public function getUpcomingFromSong(Entity\Station $station, Entity\SongInterface $song): ?Entity\StationQueue
    {
        return $this->getUpcomingBaseQuery($station)
            ->andWhere('sq.song_id = :song_id')
            ->setParameter('song_id', $song->getSongId())
            ->getQuery()
            ->setMaxResults(1)
            ->getOneOrNullResult();
    }

    protected function getUpcomingBaseQuery(Entity\Station $station): QueryBuilder
    {
        return $this->em->createQueryBuilder()
            ->select('sq, sm, sp')
            ->from(Entity\StationQueue::class, 'sq')
            ->leftJoin('sq.media', 'sm')
            ->leftJoin('sq.playlist', 'sp')
            ->where('sq.station = :station')
            ->setParameter('station', $station)
            ->orderBy('sq.timestamp_cued', 'ASC');
    }
}
