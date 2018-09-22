<?php
namespace App\Event\Radio;

use App\Entity;
use Symfony\Component\EventDispatcher\Event;

class GetNextSong extends Event
{
    const NAME = 'autodj-next-song';

    /** @var null|Entity\SongHistory The next song, if it's already calculated. */
    protected $next_song;

    /** @var Entity\Station */
    protected $station;

    public function __construct(Entity\Station $station)
    {
        $this->station = $station;
    }

    /**
     * @return Entity\SongHistory|null
     */
    public function getNextSong(): ?Entity\SongHistory
    {
        return $this->next_song;
    }

    /**
     * @param Entity\SongHistory|null $next_song
     * @return bool
     */
    public function setNextSong(?Entity\SongHistory $next_song): bool
    {
        $this->next_song = $next_song;

        if ($next_song instanceof Entity\SongHistory) {
            $this->stopPropagation();
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    public function hasNextSong(): bool
    {
        return ($this->next_song instanceof Entity\SongHistory);
    }

    /**
     * @return Entity\Station
     */
    public function getStation(): Entity\Station
    {
        return $this->station;
    }
}
