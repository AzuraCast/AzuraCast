<?php

declare(strict_types=1);

namespace App\Event\Radio;

use App\Entity\Station;
use App\Entity\StationQueue;
use App\Utilities\Time;
use Carbon\CarbonImmutable;
use Symfony\Contracts\EventDispatcher\Event;

final class BuildQueue extends Event
{
    /** @var StationQueue[] */
    private array $nextSongs = [];

    private CarbonImmutable $expectedCueTime;

    private CarbonImmutable $expectedPlayTime;

    public function __construct(
        private readonly Station $station,
        ?CarbonImmutable $expectedCueTime = null,
        ?CarbonImmutable $expectedPlayTime = null,
        private readonly ?string $lastPlayedSongId = null,
        private readonly bool $isInterrupting = false
    ) {
        $this->expectedCueTime = $expectedCueTime ?? Time::nowUtc();
        $this->expectedPlayTime = $expectedPlayTime ?? Time::nowUtc();
    }

    public function getStation(): Station
    {
        return $this->station;
    }

    public function getExpectedCueTime(): CarbonImmutable
    {
        return $this->expectedCueTime;
    }

    public function getExpectedPlayTime(): CarbonImmutable
    {
        return $this->expectedPlayTime;
    }

    public function getLastPlayedSongId(): ?string
    {
        return $this->lastPlayedSongId;
    }

    public function isInterrupting(): bool
    {
        return $this->isInterrupting;
    }

    /**
     * @return StationQueue[]
     */
    public function getNextSongs(): array
    {
        return $this->nextSongs;
    }

    /**
     * @param StationQueue|StationQueue[]|null $nextSongs
     * @return bool
     */
    public function setNextSongs(StationQueue|array|null $nextSongs): bool
    {
        if (null === $nextSongs) {
            return false;
        }

        if (!is_array($nextSongs)) {
            if ($this->lastPlayedSongId === $nextSongs->getSongId()) {
                return false;
            }

            $this->nextSongs = [$nextSongs];
        } else {
            $this->nextSongs = $nextSongs;
        }

        $this->stopPropagation();
        return true;
    }

    public function __toString(): string
    {
        return !empty($this->nextSongs)
            ? implode(', ', array_map('strval', $this->nextSongs))
            : 'No Song';
    }
}
