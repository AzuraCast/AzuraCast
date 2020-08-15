<?php
namespace App\Radio\AutoDJ;

use App\Entity;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Monolog\Logger;

class Scheduler
{
    protected Logger $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    public function shouldPlaylistPlayNow(
        Entity\StationPlaylist $playlist,
        CarbonInterface $now = null,
        array $recentSongHistory = []
    ): bool {
        $this->logger->pushProcessor(function ($record) use ($playlist) {
            $record['extra']['playlist'] = [
                'id' => $playlist->getId(),
                'name' => $playlist->getName(),
            ];
            return $record;
        });

        if (null === $now) {
            $now = CarbonImmutable::now(new \DateTimeZone($playlist->getStation()->getTimezone()));
        }

        if (!$this->isPlaylistScheduledToPlayNow($playlist, $now)) {
            $this->logger->debug('Playlist is not scheduled to play now.');
            $this->logger->popProcessor();
            return false;
        }

        $shouldPlay = true;

        switch ($playlist->getType()) {
            case Entity\StationPlaylist::TYPE_ONCE_PER_HOUR:
                $shouldPlay = $this->shouldPlaylistPlayNowPerHour($playlist, $now);

                $this->logger->debug(sprintf(
                    'Once-per-hour playlist %s been played yet this hour.',
                    $shouldPlay ? 'HAS NOT' : 'HAS'
                ));
                break;

            case Entity\StationPlaylist::TYPE_ONCE_PER_X_SONGS:
                $playPerSongs = $playlist->getPlayPerSongs();
                $shouldPlay = !$this->wasPlaylistPlayedRecently($playlist, $recentSongHistory, $playPerSongs);

                $this->logger->debug(sprintf(
                    'Once-per-X-songs playlist %s been played within the last %d song(s).',
                    $shouldPlay ? 'HAS NOT' : 'HAS',
                    $playPerSongs
                ));
                break;

            case Entity\StationPlaylist::TYPE_ONCE_PER_X_MINUTES:
                $playPerMinutes = $playlist->getPlayPerMinutes();
                $shouldPlay = !$this->wasPlaylistPlayedInLastXMinutes($playlist, $now, $playPerMinutes);

                $this->logger->debug(sprintf(
                    'Once-per-X-minutes playlist %s been played within the last %d minute(s).',
                    $shouldPlay ? 'HAS NOT' : 'HAS',
                    $playPerMinutes
                ));
                break;

            case Entity\StationPlaylist::TYPE_ADVANCED:
                $this->logger->debug('Playlist is "Advanced" type and is not managed by the AutoDJ.');
                $shouldPlay = false;
                break;
        }

        $this->logger->popProcessor();
        return $shouldPlay;
    }

    public function isPlaylistScheduledToPlayNow(
        Entity\StationPlaylist $playlist,
        CarbonInterface $now
    ): bool {
        $scheduleItems = $playlist->getScheduleItems();

        if (0 === $scheduleItems->count()) {
            $this->logger->debug('Playlist has no schedule items; skipping schedule time check.');
            return true;
        }

        foreach ($scheduleItems as $scheduleItem) {
            $scheduleName = (string)$scheduleItem;

            if ($scheduleItem->shouldPlayNow($now)) {
                $this->logger->debug(sprintf(
                    '%s - Should Play Now',
                    $scheduleName
                ));
                return true;
            } else {
                $this->logger->debug(sprintf(
                    '%s - Not Eligible to Play Now',
                    $scheduleName
                ));
            }
        }

        return false;
    }

    protected function shouldPlaylistPlayNowPerHour(
        Entity\StationPlaylist $playlist,
        CarbonInterface $now
    ): bool {
        $current_minute = (int)$now->minute;
        $target_minute = $playlist->getPlayPerHourMinute();

        if ($current_minute < $target_minute) {
            $target_time = $now->subHour()->minute($target_minute);
        } else {
            $target_time = $now->minute($target_minute);
        }

        $playlist_diff = $target_time->diffInMinutes($now, false);

        if ($playlist_diff < 0 || $playlist_diff > 15) {
            return false;
        }

        return !$this->wasPlaylistPlayedInLastXMinutes($playlist, $now, 30);
    }

    protected function wasPlaylistPlayedInLastXMinutes(
        Entity\StationPlaylist $playlist,
        CarbonInterface $now,
        int $minutes
    ): bool {
        $playedAt = $playlist->getPlayedAt();
        if (0 === $playedAt) {
            return false;
        }

        $threshold = $now->subMinutes($minutes)->getTimestamp();
        return ($playedAt > $threshold);
    }

    protected function wasPlaylistPlayedRecently(
        Entity\StationPlaylist $playlist,
        array $songHistoryEntries = [],
        int $length = 15
    ): bool {
        if (empty($songHistoryEntries)) {
            return false;
        }

        // Check if already played
        $relevant_song_history = array_slice($songHistoryEntries, 0, $length);

        $was_played = false;
        foreach ($relevant_song_history as $sh_row) {
            if ((int)$sh_row['playlist_id'] === $playlist->getId()) {
                $was_played = true;
                break;
            }
        }

        reset($songHistoryEntries);
        return $was_played;
    }

    public function shouldSchedulePlayNow(
        Entity\StationSchedule $schedule,
        CarbonInterface $now
    ): bool {
        if (!$this->shouldSchedulePlayOnCurrentDate($schedule, $now)) {
            return false;
        }

        $startTime = Entity\StationSchedule::getDateTime($schedule->getStartTime(), $now);
        $endTime = Entity\StationSchedule::getDateTime($schedule->getEndTime(), $now);

        $comparePeriods = [];

        if ($startTime->equalTo($endTime)) {
            // Create intervals for "play once" type dates.
            $endTime = $endTime->addMinutes(15);

            $comparePeriods[] = [$startTime, $endTime];
            $comparePeriods[] = [$startTime->subDay(), $endTime->subDay()];
            $comparePeriods[] = [$startTime->addDay(), $endTime->addDay()];
        } elseif ($startTime->greaterThan($endTime)) {
            // Create intervals for overnight playlists (one from yesterday to today, one from today to tomorrow).
            $comparePeriods[] = [$startTime->subDay(), $endTime];
            $comparePeriods[] = [$startTime, $endTime->addDay()];
        } else {
            $comparePeriods[] = [$startTime, $endTime];
        }

        foreach ($comparePeriods as [$start, $end]) {
            /** @var CarbonInterface $start */
            /** @var CarbonInterface $end */
            if ($now->between($start, $end)) {
                $dayToCheck = (int)$start->format('N');

                if ($this->isScheduleScheduledToPlayToday($schedule, $dayToCheck)) {
                    if ($startTime !== $endTime) {
                        return true;
                    } elseif (!$this->wasPlaylistPlayedInLastXMinutes($schedule->getPlaylist(), $now, 30)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    protected function shouldSchedulePlayOnCurrentDate(
        Entity\StationSchedule $schedule,
        CarbonInterface $now
    ): bool {
        $startDate = $schedule->getStartDate();
        $endDate = $schedule->getEndDate();

        if (!empty($startDate)) {
            $startDate = CarbonImmutable::createFromFormat('Y-m-d', $startDate, $now->getTimezone())
                ->setTime(0, 0, 0);

            if ($now->lt($startDate)) {
                return false;
            }
        }

        if (!empty($endDate)) {
            $endDate = CarbonImmutable::createFromFormat('Y-m-d', $endDate, $now->getTimezone())
                ->setTime(23, 59, 59);

            if ($now->gt($endDate)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Given a day code (1-7) a-la date('N'), return if the playlist can be played on that day.
     *
     * @param Entity\StationSchedule $schedule
     * @param int $dayToCheck
     *
     * @return bool
     */
    protected function isScheduleScheduledToPlayToday(
        Entity\StationSchedule $schedule,
        int $dayToCheck
    ): bool {
        $playOnceDays = $schedule->getDays();
        return null === $playOnceDays
            || in_array($dayToCheck, $playOnceDays, true);
    }

}