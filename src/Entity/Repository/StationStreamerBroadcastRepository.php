<?php

namespace App\Entity\Repository;

use App\Doctrine\Repository;
use App\Entity;

class StationStreamerBroadcastRepository extends Repository
{
    public function getLatestBroadcast(Entity\Station $station): ?Entity\StationStreamerBroadcast
    {
        $currentStreamer = $station->getCurrentStreamer();
        if (null === $currentStreamer) {
            return null;
        }

        /** @var Entity\StationStreamerBroadcast|null $latestBroadcast */
        $latestBroadcast = $this->em->createQuery(/** @lang DQL */ 'SELECT ssb
            FROM App\Entity\StationStreamerBroadcast ssb
            WHERE ssb.station = :station AND ssb.streamer = :streamer
            ORDER BY ssb.timestampStart DESC')
            ->setParameter('station', $station)
            ->setParameter('streamer', $currentStreamer)
            ->setMaxResults(1)
            ->getSingleResult();
        return $latestBroadcast;
    }

    public function endAllActiveBroadcasts(Entity\Station $station): void
    {
        $this->em->createQuery(/** @lang DQL */ 'UPDATE App\Entity\StationStreamerBroadcast ssb
            SET ssb.timestampEnd = :time
            WHERE ssb.station = :station
            AND ssb.timestampEnd = 0')
            ->setParameter('time', time())
            ->setParameter('station', $station)
            ->execute();
    }

    /**
     * @param Entity\Station $station
     *
     * @return Entity\StationStreamerBroadcast[]
     */
    public function getActiveBroadcasts(Entity\Station $station): array
    {
        return $this->repository->findBy([
            'station' => $station,
            'timestampEnd' => 0,
        ]);
    }
}
