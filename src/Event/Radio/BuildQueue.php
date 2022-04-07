<?php

declare(strict_types=1);

namespace App\Event\Radio;

use App\Entity;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Symfony\Contracts\EventDispatcher\Event;

class BuildQueue extends Event
{
    protected ?Entity\StationQueue $nextSong = null;

    protected CarbonInterface $expectedCueTime;

    protected CarbonInterface $expectedPlayTime;

    public function __construct(
        protected Entity\Station $station,
        ?CarbonInterface $expectedCueTime = null,
        ?CarbonInterface $expectedPlayTime = null,
        protected ?string $lastPlayedSongId = null,
    ) {
        $this->expectedCueTime = $expectedCueTime ?? CarbonImmutable::now($station->getTimezoneObject());
        $this->expectedPlayTime = $expectedPlayTime ?? CarbonImmutable::now($station->getTimezoneObject());
    }

    public function getStation(): Entity\Station
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

    public function getNextSong(): ?Entity\StationQueue
    {
        return $this->nextSong;
    }

    public function setNextSong(?Entity\StationQueue $nextSong): bool
    {
        if (null === $nextSong) {
            return false;
        }

        if ($this->lastPlayedSongId === $nextSong->getSongId()) {
            return false;
        }

        $this->nextSong = $nextSong;
        $this->stopPropagation();
        return true;
    }

    public function hasNextSong(): bool
    {
        return (null !== $this->nextSong);
    }

    public function __toString(): string
    {
        return (null !== $this->nextSong)
            ? (string)$this->nextSong
            : 'No Song';
    }
}
