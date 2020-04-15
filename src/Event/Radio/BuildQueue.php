<?php
namespace App\Event\Radio;

use App\Entity;
use Cake\Chronos\Chronos;
use Symfony\Contracts\EventDispatcher\Event;

class BuildQueue extends Event
{
    protected ?Entity\SongHistory $next_song;

    protected Entity\Station $station;

    protected Chronos $now;

    public function __construct(Entity\Station $station, ?Chronos $now = null)
    {
        $this->station = $station;

        $this->now = $now ?? Chronos::now(new \DateTimeZone($station->getTimezone()));
    }

    public function getStation(): Entity\Station
    {
        return $this->station;
    }

    public function getNow(): Chronos
    {
        return $this->now;
    }

    public function getNextSong(): ?Entity\SongHistory
    {
        return $this->next_song;
    }

    public function setNextSong(?Entity\SongHistory $next_song): bool
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

    public function __toString()
    {
        return (null !== $this->next_song)
            ? (string)$this->next_song
            : 'No Song';
    }
}
