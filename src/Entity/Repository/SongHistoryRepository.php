<?php

declare(strict_types=1);

namespace App\Entity\Repository;

use App\Doctrine\ReloadableEntityManagerInterface;
use App\Entity;
use Carbon\CarbonImmutable;
use RuntimeException;

/**
 * @extends AbstractStationBasedRepository<Entity\SongHistory>
 */
final class SongHistoryRepository extends AbstractStationBasedRepository
{
    public function __construct(
        ReloadableEntityManagerInterface $em,
        private readonly ListenerRepository $listenerRepository,
        private readonly StationQueueRepository $stationQueueRepository
    ) {
        parent::__construct($em);
    }

    /**
     * @param Entity\Station $station
     *
     * @return Entity\SongHistory[]
     */
    public function getVisibleHistory(
        Entity\Station $station,
        ?int $numEntries = null
    ): array {
        $numEntries ??= $station->getApiHistoryItems();
        if (0 === $numEntries) {
            return [];
        }

        return $this->em->createQuery(
            <<<'DQL'
                SELECT sh FROM App\Entity\SongHistory sh
                LEFT JOIN sh.media sm
                WHERE sh.station = :station
                AND sh.is_visible = 1
                ORDER BY sh.id DESC
            DQL
        )->setParameter('station', $station)
            ->setMaxResults($numEntries)
            ->execute();
    }

    public function updateSongFromNowPlaying(
        Entity\Station $station,
        Entity\Interfaces\SongInterface $song
    ): void {
        if (!$this->isDifferentFromCurrentSong($station, $song)) {
            return;
        }

        // Handle track transition.
        $upcomingTrack = $this->stationQueueRepository->findRecentlyCuedSong($station, $song);
        if (null !== $upcomingTrack) {
            $this->stationQueueRepository->trackPlayed($station, $upcomingTrack);
            $newSong = Entity\SongHistory::fromQueue($upcomingTrack);
        } else {
            $newSong = new Entity\SongHistory($station, $song);
        }

        $this->changeCurrentSong($station, $newSong);
    }

    public function updateListenersFromNowPlaying(
        Entity\Station $station,
        int $listeners
    ): Entity\SongHistory {
        $currentSong = $station->getCurrentSong();
        if (null === $currentSong) {
            throw new RuntimeException('No track to update.');
        }

        $currentSong->addDeltaPoint($listeners);
        $this->em->persist($currentSong);
        $this->em->flush();

        return $currentSong;
    }

    public function isDifferentFromCurrentSong(
        Entity\Station $station,
        Entity\Interfaces\SongInterface $toCompare
    ): bool {
        $currentSong = $station->getCurrentSong();
        return !(null !== $currentSong) || $currentSong->getSongId() !== $toCompare->getSongId();
    }

    public function changeCurrentSong(
        Entity\Station $station,
        Entity\SongHistory $newCurrentSong
    ): Entity\SongHistory {
        $previousCurrentSong = $station->getCurrentSong();

        if (null !== $previousCurrentSong) {
            // Wrapping up processing on the previous SongHistory item (if present).
            $previousCurrentSong->playbackEnded();

            $previousCurrentSong->setUniqueListeners(
                $this->listenerRepository->getUniqueListeners(
                    $station,
                    $previousCurrentSong->getTimestampStart(),
                    time()
                )
            );

            $this->em->persist($previousCurrentSong);
        }

        $newCurrentSong->setListenersFromLastSong($previousCurrentSong);
        $newCurrentSong->setTimestampStart(time());
        $newCurrentSong->updateVisibility();

        $currentStreamer = $station->getCurrentStreamer();
        if (null !== $currentStreamer) {
            $newCurrentSong->setStreamer($currentStreamer);
        }

        $this->em->persist($newCurrentSong);

        $station->setCurrentSong($newCurrentSong);
        $this->em->persist($station);

        return $newCurrentSong;
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
