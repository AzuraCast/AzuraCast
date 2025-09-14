<?php

declare(strict_types=1);

namespace App\Entity\Repository;

use App\Doctrine\Repository;
use App\Entity\Station;
use App\Entity\StationStreamer;
use App\Entity\StationStreamerBroadcast;
use App\Utilities\Time;
use Carbon\CarbonImmutable;

/**
 * @extends Repository<StationStreamerBroadcast>
 */
final class StationStreamerBroadcastRepository extends Repository
{
    protected string $entityClass = StationStreamerBroadcast::class;

    public function getLatestBroadcast(Station $station): ?StationStreamerBroadcast
    {
        $currentStreamer = $station->current_streamer;
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
                AND ssb.timestampEnd IS NULL
            DQL
        )->setParameter('time', Time::nowUtc())
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
            'timestampEnd' => null,
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

        /** @var CarbonImmutable|null $startTime */
        $startTime = CarbonImmutable::createFromFormat(
            'Ymd-His',
            $startTimeRaw,
            $station->getTimezoneObject()
        );

        if (!$startTime) {
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
            ->setParameter('start', $startTime->subMinute())
            ->setParameter('end', $startTime->addMinute())
            ->setMaxResults(1)
            ->getOneOrNullResult();

        if (null === $record) {
            $record = new StationStreamerBroadcast($streamer, $startTime);
        }

        assert($record instanceof StationStreamerBroadcast);

        $record->recordingPath = $recordingPath;
        return $record;
    }
}
