<?php

declare(strict_types=1);

namespace App\Radio\AutoDJ;

use App\Container\EntityManagerAwareTrait;
use App\Container\LoggerAwareTrait;
use App\Entity\Enums\PlaylistTypes;
use App\Entity\Repository\StationPlaylistMediaRepository;
use App\Entity\Repository\StationQueueRepository;
use App\Entity\StationPlaylist;
use App\Entity\StationSchedule;
use App\Entity\StationStreamer;
use App\Utilities\DateRange;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Doctrine\Common\Collections\Collection;
use Monolog\LogRecord;

final class Scheduler
{
    use LoggerAwareTrait;
    use EntityManagerAwareTrait;

    public function __construct(
        private readonly StationPlaylistMediaRepository $spmRepo,
        private readonly StationQueueRepository $queueRepo
    ) {
    }

    public function shouldPlaylistPlayNow(SchedulerContext $ctx): bool
    {
        //Make sure we don't give back info from a previous run.
        $ctx->clearForOutput();
        $playlist = $ctx->getPlaylistRequired();
        $this->logger->pushProcessor(
            function (LogRecord $record) use ($playlist) {
                $record->extra['playlist'] = [
                    'id' => $playlist->getId(),
                    'name' => $playlist->getName(),
                ];
                return $record;
            }
        );

        $this->logger->debug('Checking if playlist should play now.');
        if (null === $ctx->expectedPlayTime) {
            $ctx->expectedPlayTime = CarbonImmutable::now($playlist->getStation()->getTimezoneObject());
        }

        $expectedPlayTime = $ctx->getExpectedPlayTimeRequired();
        if (!$this->isPlaylistScheduledToPlayNow($ctx)) {
            $this->logger->debug('Playlist is not scheduled to play now.');
            $this->logger->popProcessor();
            return false;
        }

        $shouldPlay = true;

        switch ($playlist->getType()) {
            case PlaylistTypes::OncePerHour:
                $shouldPlay = $this->shouldPlaylistPlayNowPerHour($playlist, $expectedPlayTime);

                $this->logger->debug(
                    sprintf(
                        'Once-per-hour playlist %s been played yet this hour.',
                        $shouldPlay ? 'HAS NOT' : 'HAS'
                    )
                );
                break;

            case PlaylistTypes::OncePerXSongs:
                $playPerSongs = $playlist->getPlayPerSongs();
                $shouldPlay = !$this->queueRepo->isPlaylistRecentlyPlayed(
                    $playlist,
                    $playPerSongs,
                    $ctx->belowId
                );

                $this->logger->debug(
                    sprintf(
                        'Once-per-X-songs playlist %s been played within the last %d song(s).',
                        $shouldPlay ? 'HAS NOT' : 'HAS',
                        $playPerSongs
                    )
                );
                break;

            case PlaylistTypes::OncePerXMinutes:
                $playPerMinutes = $playlist->getPlayPerMinutes();
                $shouldPlay = !$this->wasPlaylistPlayedInLastXMinutes($playlist, $expectedPlayTime, $playPerMinutes);

                $this->logger->debug(
                    sprintf(
                        'Once-per-X-minutes playlist %s been played within the last %d minute(s).',
                        $shouldPlay ? 'HAS NOT' : 'HAS',
                        $playPerMinutes
                    )
                );
                break;

            case PlaylistTypes::Advanced:
                $this->logger->debug('Playlist is "Advanced" type and is not managed by the AutoDJ.');
                $shouldPlay = false;
                break;

            case PlaylistTypes::Standard:
                break;
        }

        $this->logger->popProcessor();
        return $shouldPlay;
    }

    public function isPlaylistScheduledToPlayNow(SchedulerContext $ctx): bool
    {
        $playlist = $ctx->getPlaylistRequired();
        $scheduleItems = $playlist->getScheduleItems();

        if (0 === $scheduleItems->count()) {
            $this->logger->debug('Playlist has no schedule items; skipping schedule time check.');
            return true;
        }

        $scheduleItem = $this->getActiveScheduleFromCollection($scheduleItems, $ctx);
        return null !== $scheduleItem;
    }

    private function shouldPlaylistPlayNowPerHour(
        StationPlaylist $playlist,
        CarbonInterface $now
    ): bool {
        $currentMinute = $now->minute;
        $targetMinute = $playlist->getPlayPerHourMinute();

        if ($currentMinute < $targetMinute) {
            $targetTime = $now->subHour()->minute($targetMinute);
        } else {
            $targetTime = $now->minute($targetMinute);
        }

        $playlistDiff = $targetTime->diffInMinutes($now);

        if ($playlistDiff < 0 || $playlistDiff > 15) {
            return false;
        }

        return !$this->wasPlaylistPlayedInLastXMinutes($playlist, $now, 30);
    }

    private function wasPlaylistPlayedInLastXMinutes(
        StationPlaylist $playlist,
        CarbonInterface $now,
        int $minutes
    ): bool {
        $playedAt = $this->queueRepo->getLastPlayedTimeForPlaylist($playlist, $now);

        if (0 === $playedAt) {
            return false;
        }

        $threshold = $now->subMinutes($minutes)->getTimestamp();
        return ($playedAt > $threshold);
    }

    /**
     * Get the duration of scheduled play time in seconds (used for remote URLs of indeterminate length).
     */
    public function getPlaylistScheduleDuration(StationPlaylist $playlist): int
    {
        $now = CarbonImmutable::now($playlist->getStation()->getTimezoneObject());
        $scheduleItem = $this->getActiveScheduleFromCollection(
            $playlist->getScheduleItems(),
            new SchedulerContext(
                null,
                $now
            )
        );
        if ($scheduleItem instanceof StationSchedule) {
            return $scheduleItem->getDuration();
        }
        return 0;
    }

    public function canStreamerStreamNow(
        StationStreamer $streamer,
        CarbonInterface $now = null
    ): bool {
        if (!$streamer->enforceSchedule()) {
            return true;
        }

        if (null === $now) {
            $now = CarbonImmutable::now($streamer->getStation()->getTimezoneObject());
        }

        $scheduleItem = $this->getActiveScheduleFromCollection(
            $streamer->getScheduleItems(),
            new SchedulerContext(
                null,
                $now
            )
        );

        return null !== $scheduleItem;
    }

    /**
     * @param Collection<int, StationSchedule> $scheduleItems
     * @param SchedulerContext $ctx
     * @return StationSchedule|null
     */
    private function getActiveScheduleFromCollection(Collection $scheduleItems, SchedulerContext $ctx): ?StationSchedule
    {
        if ($scheduleItems->count() > 0) {
            foreach ($scheduleItems as $scheduleItem) {
                $scheduleName = (string)$scheduleItem;

                if ($this->shouldSchedulePlayNow($scheduleItem, $ctx)) {
                    $this->logger->debug(
                        sprintf(
                            '%s - Should Play Now',
                            $scheduleName
                        )
                    );

                    return $scheduleItem;
                }

                $this->logger->debug(
                    sprintf(
                        '%s - Not Eligible to Play Now',
                        $scheduleName
                    )
                );
            }
        }
        return null;
    }

    public function shouldSchedulePlayNow(StationSchedule $schedule, SchedulerContext $ctx): bool
    {
        $now = $ctx->getExpectedPlayTimeRequired();
        $startTime = StationSchedule::getDateTime($schedule->getStartTime(), $now);
        $endTime = StationSchedule::getDateTime($schedule->getEndTime(), $now);
        $this->logger->debug('Checking to see whether schedule should play now.', [
            'startTime' => $startTime,
            'endTime' => $endTime,
        ]);

        if (!$this->shouldSchedulePlayOnCurrentDate($schedule, $now)) {
            $this->logger->debug('Schedule is not scheduled to play today.');
            return false;
        }

        /** @var DateRange[] $comparePeriods */
        $comparePeriods = [];

        if ($startTime->equalTo($endTime)) {
            // Create intervals for "play once" type dates.
            $comparePeriods[] = new DateRange(
                $startTime,
                $endTime->addMinutes(15)
            );
            $comparePeriods[] = new DateRange(
                $startTime->subDay(),
                $endTime->subDay()->addMinutes(15)
            );
            $comparePeriods[] = new DateRange(
                $startTime->addDay(),
                $endTime->addDay()->addMinutes(15)
            );
        } elseif ($startTime->greaterThan($endTime)) {
            // Create intervals for overnight playlists (one from yesterday to today, one from today to tomorrow).
            $comparePeriods[] = new DateRange(
                $startTime->subDay(),
                $endTime
            );
            $comparePeriods[] = new DateRange(
                $startTime,
                $endTime->addDay()
            );
        } else {
            $comparePeriods[] = new DateRange(
                $startTime,
                $endTime
            );
        }

        foreach ($comparePeriods as $dateRange) {
            if ($this->shouldPlayInSchedulePeriod($schedule, $dateRange, $ctx)) {
                //Report final results to the context for retrieval by the caller.
                $ctx->schedule = $schedule;
                $ctx->dateRange = $dateRange;
                return true;
            }
        }

        return false;
    }

    private function shouldPlayInSchedulePeriod(
        StationSchedule $schedule,
        DateRange $dateRange,
        SchedulerContext $ctx
    ): bool {
        $now = $ctx->getExpectedPlayTimeRequired();
        if (!$dateRange->contains($now)) {
            return false;
        }

        // Check day-of-week limitations.
        $dayToCheck = $dateRange->getStart()->dayOfWeekIso;
        if (!$this->isScheduleScheduledToPlayToday($schedule, $dayToCheck)) {
            return false;
        }

        // Check playlist special handling rules.
        $playlist = $schedule->getPlaylist();
        if (null === $playlist) {
            return true;
        }

        // Skip the remaining checks if we're doing a "still scheduled to play" Queue check.
        if ($ctx->excludeSpecialRules) {
            return true;
        }

        // Handle "Play Single Track" advanced setting.
        if (
            $playlist->backendPlaySingleTrack()
            && $playlist->getPlayedAt() >= $dateRange->getStartTimestamp()
        ) {
            return false;
        }

        // Handle "Loop Once" schedule specification.
        if (
            $schedule->getLoopOnce()
            && !$this->shouldPlaylistLoopNow($schedule, $dateRange, $now)
        ) {
            return false;
        }

        return true;
    }

    private function shouldPlaylistLoopNow(
        StationSchedule $schedule,
        DateRange $dateRange,
        CarbonInterface $now,
    ): bool {
        $this->logger->debug('Checking if playlist should loop now.');

        $playlist = $schedule->getPlaylist();

        if (null === $playlist) {
            $this->logger->error('Attempting to check playlist loop status on a non-playlist-based schedule item.');
            return false;
        }

        $scheduleRunStarted = (
            null !== $this->queueRepo->getStartOfScheduleRun(
                $playlist->getStation(),
                $schedule,
                $dateRange->getStartTimestamp()
            )
        );

        $isQueueEmpty = $this->spmRepo->isQueueEmpty($playlist);
        if (!$scheduleRunStarted) {
            $this->logger->debug('Playlist was not played yet.');
            $this->logger->debug('Resetting playlist queue with now override.');
            $startOfWindow = $dateRange->getStart()->subSecond();
            $this->spmRepo->resetQueue($playlist, $startOfWindow);
            $isQueueEmpty = false;
        }

        $playlist = $this->em->refetch($playlist);
        $playlistQueueResetAt = CarbonImmutable::createFromTimestamp(
            $playlist->getQueueResetAt(),
            $now->getTimezone()
        );

        if (!$isQueueEmpty && !$dateRange->contains($playlistQueueResetAt)) {
            $this->logger->debug('Playlist should loop.');
            return true;
        }

        $this->logger->debug('Playlist should NOT loop.');
        return false;
    }

    public function shouldSchedulePlayOnCurrentDate(
        StationSchedule $schedule,
        CarbonInterface $now
    ): bool {
        $startDate = $schedule->getStartDate();
        $endDate = $schedule->getEndDate();

        if (!empty($startDate)) {
            $startDate = CarbonImmutable::createFromFormat('Y-m-d', $startDate, $now->getTimezone());

            if (null !== $startDate) {
                $startDate = StationSchedule::getDateTime(
                    $schedule->getStartTime(),
                    $startDate
                );
                if ($now->endOfDay()->lt($startDate)) {
                    return false;
                }
            }
        }

        if (!empty($endDate)) {
            $endDate = CarbonImmutable::createFromFormat('Y-m-d', $endDate, $now->getTimezone());

            if (null !== $endDate) {
                $endDate = StationSchedule::getDateTime(
                    $schedule->getEndTime(),
                    $endDate
                );
                if ($now->startOfDay()->gt($endDate)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Given an ISO-8601 date, return if the playlist can be played on that day.
     *
     * @param StationSchedule $schedule
     * @param int $dayToCheck ISO-8601 date (1 for Monday, 7 for Sunday)
     */
    public function isScheduleScheduledToPlayToday(
        StationSchedule $schedule,
        int $dayToCheck
    ): bool {
        $playOnceDays = $schedule->getDays();
        return empty($playOnceDays)
            || in_array($dayToCheck, $playOnceDays, true);
    }
}
