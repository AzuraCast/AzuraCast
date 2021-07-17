<?php

declare(strict_types=1);

namespace App\Entity\Repository;

use App\Doctrine\ReloadableEntityManagerInterface;
use App\Doctrine\Repository;
use App\Entity;
use App\Environment;
use Carbon\CarbonImmutable;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Serializer;

class SongHistoryRepository extends Repository
{
    protected ListenerRepository $listenerRepository;

    protected StationQueueRepository $stationQueueRepository;

    public function __construct(
        ReloadableEntityManagerInterface $em,
        Serializer $serializer,
        Environment $environment,
        LoggerInterface $logger,
        ListenerRepository $listenerRepository,
        StationQueueRepository $stationQueueRepository
    ) {
        $this->listenerRepository = $listenerRepository;
        $this->stationQueueRepository = $stationQueueRepository;

        parent::__construct($em, $serializer, $environment, $logger);
    }

    /**
     * @param Entity\Station $station
     *
     * @return Entity\SongHistory[]
     */
    public function getVisibleHistory(Entity\Station $station): array
    {
        $numEntries = $station->getApiHistoryItems();
        if (0 === $numEntries) {
            return [];
        }

        $recordsRaw = $this->em->createQuery(
            <<<'DQL'
                SELECT sh FROM App\Entity\SongHistory sh
                LEFT JOIN sh.media sm
                WHERE sh.station_id = :station_id
                AND sh.timestamp_end != 0
                ORDER BY sh.id DESC
            DQL
        )->setParameter('station_id', $station->getId())
            ->setMaxResults($numEntries)
            ->execute();

        $records = [];
        foreach ($recordsRaw as $row) {
            /** @var Entity\SongHistory $row */
            if ($row->showInApis()) {
                $records[] = $row;
            }
        }
        return $records;
    }

    public function register(
        Entity\Interfaces\SongInterface $song,
        Entity\Station $station,
        Entity\Api\NowPlaying $np
    ): Entity\SongHistory {
        // Pull the most recent history item for this station.
        $last_sh = $this->getCurrent($station);

        $listeners = $np->listeners->current;

        if ($last_sh instanceof Entity\SongHistory) {
            if ($last_sh->getSongId() === $song->getSongId()) {
                // Updating the existing SongHistory item with a new data point.
                $last_sh->addDeltaPoint($listeners);

                $this->em->persist($last_sh);
                $this->em->flush();

                return $last_sh;
            }

            // Wrapping up processing on the previous SongHistory item (if present).
            $last_sh->setTimestampEnd(time());
            $last_sh->setListenersEnd($listeners);

            // Calculate "delta" data for previous item, based on all data points.
            $last_sh->addDeltaPoint($listeners);

            $delta_points = (array)$last_sh->getDeltaPoints();

            $delta_positive = 0;
            $delta_negative = 0;
            $delta_total = 0;

            for ($i = 1, $iMax = count($delta_points); $i < $iMax; $i++) {
                $current_delta = $delta_points[$i];
                $previous_delta = $delta_points[$i - 1];

                $delta_delta = $current_delta - $previous_delta;
                $delta_total += $delta_delta;

                if ($delta_delta > 0) {
                    $delta_positive += $delta_delta;
                } elseif ($delta_delta < 0) {
                    $delta_negative += (int)abs($delta_delta);
                }
            }

            $last_sh->setDeltaPositive($delta_positive);
            $last_sh->setDeltaNegative($delta_negative);
            $last_sh->setDeltaTotal($delta_total);

            $last_sh->setUniqueListeners(
                $this->listenerRepository->getUniqueListeners(
                    $station,
                    $last_sh->getTimestampStart(),
                    time()
                )
            );

            $this->em->persist($last_sh);
        }

        // Look for an already cued but unplayed song.
        $sq = $this->stationQueueRepository->findRecentlyCuedSong($station, $song);

        if ($sq instanceof Entity\StationQueue) {
            $sh = Entity\SongHistory::fromQueue($sq);
        } else {
            // Processing a new SongHistory item.
            $sh = new Entity\SongHistory($station, $song);

            $currentStreamer = $station->getCurrentStreamer();
            if ($currentStreamer instanceof Entity\StationStreamer) {
                $sh->setStreamer($currentStreamer);
            }
        }

        $sh->setTimestampStart(time());
        $sh->setListenersStart($listeners);
        $sh->addDeltaPoint($listeners);

        $this->em->persist($sh);
        $this->em->flush();

        return $sh;
    }

    public function getCurrent(Entity\Station $station): ?Entity\SongHistory
    {
        return $this->em->createQuery(
            <<<'DQL'
                SELECT sh
                FROM App\Entity\SongHistory sh
                WHERE sh.station = :station
                AND sh.timestamp_start != 0
                AND (sh.timestamp_end IS NULL OR sh.timestamp_end = 0)
                ORDER BY sh.timestamp_start DESC
            DQL
        )->setParameter('station', $station)
            ->setMaxResults(1)
            ->getOneOrNullResult();
    }

    /**
     * @param Entity\Station $station
     * @param int $start
     * @param int $end
     *
     * @return mixed[] [int $minimumListeners, int $maximumListeners, float $averageListeners]
     */
    public function getStatsByTimeRange(Entity\Station $station, int $start, int $end): array
    {
        $historyTotals = $this->em->createQuery(
            <<<'DQL'
                SELECT AVG(sh.listeners_end) AS listeners_avg, MAX(sh.listeners_end) AS listeners_max,
                    MIN(sh.listeners_end) AS listeners_min
                FROM App\Entity\SongHistory sh
                WHERE sh.station = :station
                AND sh.timestamp_end >= :start
                AND sh.timestamp_start <= :end
            DQL
        )->setParameter('station', $station)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getSingleResult();

        $min = (int)$historyTotals['listeners_min'];
        $max = (int)$historyTotals['listeners_max'];
        $avg = round((float)$historyTotals['listeners_avg'], 2);

        return [$min, $max, $avg];
    }

    public function cleanup(int $daysToKeep): void
    {
        $threshold = CarbonImmutable::now()
            ->subDays($daysToKeep)
            ->getTimestamp();

        $this->em->createQuery(
            <<<'DQL'
                DELETE FROM App\Entity\SongHistory sh
                WHERE sh.timestamp_start != 0
                AND sh.timestamp_start <= :threshold
            DQL
        )->setParameter('threshold', $threshold)
            ->execute();
    }
}
