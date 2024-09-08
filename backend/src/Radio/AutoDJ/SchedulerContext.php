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
    private ?StationPlaylist $playlist = null;
    private ?CarbonInterface $now = null;
    private ?StationSchedule $schedule = null;
    private ?DateRange $dateRange = null;
    private bool $excludeSpecialRules = false;
    private ?int $belowId = null;
    // inputs.
    public function withPlaylist(?StationPlaylist $playlist): self
    {
        $this->playlist = $playlist;
        return $this;
    }
    public function withNow(CarbonInterface $now): self
    {
        $this->now = $now;
        return $this;
    }
    public function withExcludeSpecialRules(bool $excludeSpecialRules): self
    {
        $this->excludeSpecialRules = $excludeSpecialRules;
        return $this;
    }
    public function withBelowId(?int $belowId): self
    {
        $this->belowId = $belowId;
        return $this;
    }
    // outputs.
    public function withSchedule(StationSchedule $schedule): self
    {
        $this->schedule = $schedule;
        return $this;
    }
    public function withDateRange(DateRange $dateRange): self
    {
        $this->dateRange = $dateRange;
        return $this;
    }
    public function getPlaylist(): StationPlaylist|null
    {
        return $this->playlist;
    }
    public function getPlaylistRequired(): StationPlaylist
    {
        if (null === $this->playlist) {
            throw new RuntimeException("No playlist given.");
        }
        return $this->playlist;
    }
    public function getNow(): CarbonInterface|null
    {
        return $this->now;
    }
    public function getNowRequired(): CarbonInterface
    {
        if (null === $this->now) {
            throw new RuntimeException("No now given.");
        }
        return $this->now;
    }
    public function getExcludeSpecialRules(): bool
    {
        return $this->excludeSpecialRules;
    }
    public function getBelowId(): int|null
    {
        return $this->belowId;
    }
    public function getSchedule(): StationSchedule|null
    {
        return $this->schedule;
    }
    public function getDateRange(): DateRange|null
    {
        return $this->dateRange;
    }
    public function clearForOutput(): void
    {
        $this->schedule = null;
        $this->dateRange = null;
    }
}
