<?php

declare(strict_types=1);

namespace App\Entity\Repository;

use App\Doctrine\Repository;
use App\Entity\Station;
use App\Entity\StationStreamer;
use App\Entity\StationStreamerBroadcast;
use Carbon\CarbonImmutable;

/**
 * @extends Repository<StationStreamerBroadcast>
 */
final class StationStreamerBroadcastRepository extends Repository
{
    protected string $entityClass = StationStreamerBroadcast::class;

    public function getLatestBroadcast(Station $station): ?StationStreamerBroadcast
    {
        $currentStreamer = $station->getCurrentStreamer();
        if (null === $currentStreamer) {
            return null;
        }

        /** @var StationStreamerBroadcast|null $latestBroadcast */
        $latestBroadcast = $this->em->createQuery(
            <<<'DQL'
                SELECT ssb
                FROM App\Entity\StationStreamerBroadcast ssb
                WHERE ssb.station = :station AND ssb.streamer = :streamer
                ORDER BY ssb.timestampStart DESC
            DQL
        )->setParameter('station', $station)
            ->setParameter('streamer', $currentStreamer)
            ->setMaxResults(1)
            ->getSingleResult();

        return $latestBroadcast;
    }

    public function endAllActiveBroadcasts(Station $station): void
    {
        $this->em->createQuery(
            <<<'DQL'
                UPDATE App\Entity\StationStreamerBroadcast ssb
                SET ssb.timestampEnd = :time
                WHERE ssb.station = :station
                AND ssb.timestampEnd = 0
            DQL
        )->setParameter('time', time())
            ->setParameter('station', $station)
            ->execute();
    }

    /**
     * @param Station $station
     *
     * @return StationStreamerBroadcast[]
     */
    public function getActiveBroadcasts(Station $station): array
    {
        return $this->repository->findBy([
            'station' => $station,
            'timestampEnd' => 0,
        ]);
    }

    public function findByPath(Station $station, string $path): ?StationStreamerBroadcast
    {
        return $this->repository->findOneBy([
            'station' => $station,
            'recordingPath' => $path,
        ]);
    }

    public function getOrCreateFromPath(
        Station $station,
        string $recordingPath,
    ): ?StationStreamerBroadcast {
        $streamerUsername = pathinfo($recordingPath, PATHINFO_DIRNAME);

        $streamer = $this->em->getRepository(StationStreamer::class)
            ->findOneBy([
                'station' => $station,
                'streamer_username' => $streamerUsername,
                'is_active' => 1,
            ]);

        if (null === $streamer) {
            return null;
        }

        $startTimeRaw = str_replace(
            StationStreamerBroadcast::PATH_PREFIX . '_',
            '',
            pathinfo($recordingPath, PATHINFO_FILENAME)
        );
        $startTime = CarbonImmutable::createFromFormat(
            'Ymd-His',
            $startTimeRaw,
            $station->getTimezoneObject()
        );

        if (false === $startTime) {
            return null;
        }

        $record = $this->em->createQuery(
            <<<'DQL'
            SELECT ssb
            FROM App\Entity\StationStreamerBroadcast ssb
            WHERE ssb.streamer = :streamer
            AND ssb.timestampStart >= :start AND ssb.timestampStart <= :end
            AND ssb.recordingPath IS NULL  
            DQL
        )->setParameter('streamer', $streamer)
            ->setParameter('start', $startTime->subMinute()->getTimestamp())
            ->setParameter('end', $startTime->addMinute()->getTimestamp())
            ->setMaxResults(1)
            ->getOneOrNullResult();

        if (null === $record) {
            $record = new StationStreamerBroadcast($streamer);
        }

        $record->setTimestampStart($startTime->getTimestamp());
        $record->setRecordingPath($recordingPath);
        return $record;
    }
}
