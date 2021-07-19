<?php

declare(strict_types=1);

namespace App\Event\Radio;

use App\Entity;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Symfony\Contracts\EventDispatcher\Event;

class BuildQueue extends Event
{
    protected ?Entity\StationQueue $next_song = null;

    protected CarbonInterface $now;

    public function __construct(
        protected Entity\Station $station,
        ?CarbonInterface $now = null
    ) {
        $this->now = $now ?? CarbonImmutable::now($station->getTimezoneObject());
    }

    public function getStation(): Entity\Station
    {
        return $this->station;
    }

    public function getNow(): CarbonInterface
    {
        return $this->now;
    }

    public function getNextSong(): ?Entity\StationQueue
    {
        return $this->next_song;
    }

    public function setNextSong(?Entity\StationQueue $next_song): bool
    {
        $this->next_song = $next_song;

        if (null !== $next_song) {
            $this->stopPropagation();
            return true;
        }

        return false;
    }

    public function hasNextSong(): bool
    {
        return (null !== $this->next_song);
    }

    public function __toString(): string
    {
        return (null !== $this->next_song)
            ? (string)$this->next_song
            : 'No Song';
    }
}
