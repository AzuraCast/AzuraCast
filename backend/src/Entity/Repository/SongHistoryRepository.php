<?php

declare(strict_types=1);

namespace App\Entity\Repository;

use App\Entity\Interfaces\SongInterface;
use App\Entity\SongHistory;
use App\Entity\Station;
use App\Utilities\Time;
use Carbon\CarbonImmutable;
use DateTimeImmutable;
use RuntimeException;

/**
 * @extends AbstractStationBasedRepository<SongHistory>
 */
final class SongHistoryRepository extends AbstractStationBasedRepository
{
    protected string $entityClass = SongHistory::class;

    public function __construct(
        private readonly ListenerRepository $listenerRepository,
        private readonly StationQueueRepository $stationQueueRepository
    ) {
    }

    /**
     * @param Station $station
     *
     * @return SongHistory[]
     */
    public function getVisibleHistory(
        Station $station,
        ?int $numEntries = null
    ): array {
        $numEntries ??= $station->api_history_items;
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
        Station $station,
        SongInterface $song
    ): void {
        if (!$this->isDifferentFromCurrentSong($station, $song)) {
            return;
        }

        // Handle track transition.
        $upcomingTrack = $this->stationQueueRepository->findRecentlyCuedSong($station, $song);
        if (null !== $upcomingTrack) {
            $this->stationQueueRepository->trackPlayed($station, $upcomingTrack);
            $newSong = SongHistory::fromQueue($upcomingTrack);
        } else {
            $newSong = new SongHistory($station, $song);
        }

        $this->changeCurrentSong($station, $newSong);
    }

    public function updateListenersFromNowPlaying(
        Station $station,
        int $listeners
    ): SongHistory {
        $currentSong = $station->current_song;
        if (null === $currentSong) {
            throw new RuntimeException('No track to update.');
        }

        $currentSong->addDeltaPoint($listeners);
        $this->em->persist($currentSong);
        $this->em->flush();

        return $currentSong;
    }

    public function isDifferentFromCurrentSong(
        Station $station,
        SongInterface $toCompare
    ): bool {
        $currentSong = $station->current_song;
        return !(null !== $currentSong) || $currentSong->song_id !== $toCompare->song_id;
    }

    public function changeCurrentSong(
        Station $station,
        SongHistory $newCurrentSong
    ): SongHistory {
        $previousCurrentSong = $station->current_song;

        if (null !== $previousCurrentSong) {
            // Wrapping up processing on the previous SongHistory item (if present).
            $previousCurrentSong->playbackEnded();

            $previousCurrentSong->unique_listeners = $this->listenerRepository->getUniqueListeners(
                $station,
                $previousCurrentSong->timestamp_start,
                Time::nowUtc()
            );

            $this->em->persist($previousCurrentSong);
        }

        $newCurrentSong->setListenersFromLastSong($previousCurrentSong);
        $newCurrentSong->updateVisibility();

        $currentStreamer = $station->current_streamer;
        if (null !== $currentStreamer) {
            $newCurrentSong->streamer = $currentStreamer;
        }

        $this->em->persist($newCurrentSong);

        $station->current_song = $newCurrentSong;
        $this->em->persist($station);

        return $newCurrentSong;
    }

    /**
     * @param Station $station
     * @param DateTimeImmutable $start
     * @param DateTimeImmutable $end
     *
     * @return array{int, int, float}
     */
    public function getStatsByTimeRange(Station $station, DateTimeImmutable $start, DateTimeImmutable $end): array
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

    public function getEarliestRecordTime(): ?CarbonImmutable
    {
        $earliestRecord = $this->em->createQuery(
            <<<'DQL'
                SELECT sh
                FROM App\Entity\SongHistory sh
                ORDER BY sh.timestamp_start ASC
            DQL
        )->setMaxResults(1)
            ->getOneOrNullResult();

        return ($earliestRecord instanceof SongHistory)
            ? Time::toUtcCarbonImmutable($earliestRecord->timestamp_start)
            : null;
    }

    public function cleanup(int $daysToKeep): void
    {
        $threshold = Time::nowUtc()->subDays($daysToKeep);

        $this->em->createQuery(
            <<<'DQL'
                DELETE FROM App\Entity\SongHistory sh
                WHERE sh.timestamp_start <= :threshold
            DQL
        )->setParameter('threshold', $threshold)
            ->execute();
    }
}
