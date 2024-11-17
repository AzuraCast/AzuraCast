<?php

declare(strict_types=1);

namespace App\Radio\AutoDJ;

use App\Entity\Station;
use App\Entity\StationPlaylist;
use App\Entity\StationSchedule;
use App\Utilities\DateRange;
use Carbon\CarbonInterface;
use RuntimeException;

/**
 * Inputs and outputs for a single scheduling task.
 */
final class SchedulerContext
{
    public ?StationSchedule $schedule = null;
    public ?DateRange $dateRange = null;
    public ?int $belowId = null;

    public function __construct(
        public ?StationPlaylist $playlist = null,
        public ?CarbonInterface $expectedPlayTime = null,
        public bool $excludeSpecialRules = false
    ) {
    }

    public function clearForOutput(): void
    {
        $this->schedule = null;
        $this->dateRange = null;
    }

    /**
     * Returns playlist, or throws if null.
     */
    public function getPlaylistRequired(): StationPlaylist
    {
        if (null === $this->playlist) {
            throw new RuntimeException('"playlist" is required but not set.');
        }

        return $this->playlist;
    }

    public function getExpectedPlayTimeRequired(): CarbonInterface
    {
        if (null === $this->expectedPlayTime) {
            throw new RuntimeException('"expectedPlayTime" is required but not set.');
        }

        return $this->expectedPlayTime;
    }
}
