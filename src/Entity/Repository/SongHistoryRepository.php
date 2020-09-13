<?php
namespace App\Entity\Repository;

use App\ApiUtilities;
use App\Doctrine\Repository;
use App\Entity;
use App\Settings;
use Carbon\CarbonInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Serializer;

class SongHistoryRepository extends Repository
{
    protected ListenerRepository $listenerRepository;

    protected StationQueueRepository $stationQueueRepository;

    public function __construct(
        EntityManagerInterface $em,
        Serializer $serializer,
        Settings $settings,
        LoggerInterface $logger,
        ListenerRepository $listenerRepository,
        StationQueueRepository $stationQueueRepository
    ) {
        $this->listenerRepository = $listenerRepository;
        $this->stationQueueRepository = $stationQueueRepository;

        parent::__construct($em, $serializer, $settings, $logger);
    }

    /**
     * @param Entity\Station $station
     * @param ApiUtilities $apiUtils
     * @param UriInterface|null $baseUrl
     *
     * @return Entity\Api\SongHistory[]
     */
    public function getHistoryApi(
        Entity\Station $station,
        ApiUtilities $apiUtils,
        UriInterface $baseUrl = null
    ): array {
        $num_entries = $station->getApiHistoryItems();

        if ($num_entries === 0) {
            return [];
        }

        $history = $this->em->createQuery(/** @lang DQL */ 'SELECT sh, s
            FROM App\Entity\SongHistory sh
            JOIN sh.song s
            LEFT JOIN sh.media sm
            WHERE sh.station_id = :station_id
            AND sh.timestamp_end != 0
            ORDER BY sh.id DESC')
            ->setParameter('station_id', $station->getId())
            ->setMaxResults($num_entries)
            ->execute();

        $return = [];
        foreach ($history as $sh) {
            /** @var Entity\SongHistory $sh */
            if ($sh->showInApis()) {
                $return[] = $sh->api(new Entity\Api\SongHistory, $apiUtils, $baseUrl);
            }
        }

        return $return;
    }

    public function getRecentlyPlayed(
        Entity\Station $station,
        CarbonInterface $now,
        int $rows
    ): array {
        $recentlyPlayed = $this->em->createQuery(/** @lang DQL */ 'SELECT sq, s
            FROM App\Entity\StationQueue sq JOIN sq.song s
            WHERE sq.station = :station
            ORDER BY sq.timestamp_cued DESC')
            ->setParameter('station', $station)
            ->setMaxResults($rows)
            ->getArrayResult();

        $recentHistory = $this->em->createQuery(/** @lang DQL */ 'SELECT sh, s
            FROM App\Entity\SongHistory sh JOIN sh.song s
            WHERE sh.station = :station
            AND (sh.timestamp_start != 0 AND sh.timestamp_start IS NOT NULL)
            AND sh.timestamp_start >= :threshold
            ORDER BY sh.timestamp_start DESC')
            ->setParameter('station', $station)
            ->setParameter('threshold', $now->subDay()->getTimestamp())
            ->setMaxResults($rows)
            ->getArrayResult();

        $recentlyPlayed = array_merge($recentlyPlayed, $recentHistory);
        return array_slice($recentlyPlayed, 0, $rows);
    }

    public function getRecentlyPlayedByTimeRange(
        Entity\Station $station,
        CarbonInterface $now,
        int $minutes
    ): array {
        $timeRangeInSeconds = $minutes * 60;
        $threshold = $now->getTimestamp() - $timeRangeInSeconds;

        $recentlyPlayed = $this->em->createQuery(/** @lang DQL */ 'SELECT sq, s
            FROM App\Entity\StationQueue sq JOIN sq.song s
            WHERE sq.station = :station
            AND sq.timestamp_cued >= :threshold
            ORDER BY sq.timestamp_cued DESC')
            ->setParameter('station', $station)
            ->setParameter('threshold', $threshold)
            ->getArrayResult();

        $recentHistory = $this->em->createQuery(/** @lang DQL */ 'SELECT sh, s
            FROM App\Entity\SongHistory sh JOIN sh.song s
            WHERE sh.station = :station
            AND (sh.timestamp_start != 0 AND sh.timestamp_start IS NOT NULL)
            AND sh.timestamp_start >= :threshold
            ORDER BY sh.timestamp_start DESC')
            ->setParameter('station', $station)
            ->setParameter('threshold', $threshold)
            ->getArrayResult();

        return array_merge($recentlyPlayed, $recentHistory);
    }

    public function register(
        Entity\Song $song,
        Entity\Station $station,
        Entity\Api\NowPlaying $np
    ): Entity\SongHistory {
        // Pull the most recent history item for this station.
        $last_sh = $this->getCurrent($station);

        $listeners = (int)$np->listeners->current;

        if ($last_sh instanceof Entity\SongHistory) {
            if ($last_sh->getSong() === $song) {
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
                    $delta_negative += abs($delta_delta);
                }
            }

            $last_sh->setDeltaPositive($delta_positive);
            $last_sh->setDeltaNegative($delta_negative);
            $last_sh->setDeltaTotal($delta_total);

            $last_sh->setUniqueListeners($this->listenerRepository->getUniqueListeners($station,
                $last_sh->getTimestampStart(),
                time()));

            $this->em->persist($last_sh);
        }

        // Look for an already cued but unplayed song.
        $sq = $this->stationQueueRepository->getUpcomingFromSong($station, $song);

        if ($sq instanceof Entity\StationQueue) {
            $sh = Entity\SongHistory::fromQueue($sq);

            $this->em->remove($sq);
        } else {
            // Processing a new SongHistory item.
            $sh = new Entity\SongHistory($song, $station);

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
        return $this->em->createQuery(/** @lang DQL */ 'SELECT sh
            FROM App\Entity\SongHistory sh
            WHERE sh.station = :station
            AND sh.timestamp_start != 0
            AND (sh.timestamp_end IS NULL OR sh.timestamp_end = 0)
            ORDER BY sh.timestamp_start DESC')
            ->setParameter('station', $station)
            ->setMaxResults(1)
            ->getOneOrNullResult();
    }
}
