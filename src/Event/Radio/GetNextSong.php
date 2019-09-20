<?php
namespace App\Event\Radio;

use App\Entity;
use Symfony\Contracts\EventDispatcher\Event;

class GetNextSong extends Event
{
    /** @var null|string|Entity\SongHistory The next song, if it's already calculated. */
    protected $next_song;

    /** @var Entity\Station */
    protected $station;

    public function __construct(Entity\Station $station)
    {
        $this->station = $station;
    }

    /**
     * @return Entity\SongHistory|string|null
     */
    public function getNextSong()
    {
        return $this->next_song;
    }

    /**
     * @param Entity\SongHistory|string|null $next_song
     *
     * @return bool
     */
    public function setNextSong($next_song): bool
    {
        $this->next_song = $next_song;

        if (null !== $next_song) {
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
        return (null !== $this->next_song);
    }

    /**
     * @return Entity\Station
     */
    public function getStation(): Entity\Station
    {
        return $this->station;
    }
}
