<?php

declare(strict_types=1);

namespace App\Event\Radio;

use App\Entity\Station;
use App\Entity\StationQueue;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Symfony\Contracts\EventDispatcher\Event;

final class BuildQueue extends Event
{
    /** @var StationQueue[] */
    private array $nextSongs = [];

    private CarbonInterface $expectedCueTime;

    private CarbonInterface $expectedPlayTime;

    public function __construct(
        private readonly Station $station,
        ?CarbonInterface $expectedCueTime = null,
        ?CarbonInterface $expectedPlayTime = null,
        private readonly ?string $lastPlayedSongId = null,
        private readonly bool $isInterrupting = false
    ) {
        $this->expectedCueTime = $expectedCueTime ?? CarbonImmutable::now($station->getTimezoneObject());
        $this->expectedPlayTime = $expectedPlayTime ?? CarbonImmutable::now($station->getTimezoneObject());
    }

    public function getStation(): Station
    {
        return $this->station;
    }

    public function getExpectedCueTime(): CarbonInterface
    {
        return $this->expectedCueTime;
    }

    public function getExpectedPlayTime(): CarbonInterface
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
