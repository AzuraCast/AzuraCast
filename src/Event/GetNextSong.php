<?php
namespace App\Event;

use App\Entity;
use Symfony\Component\EventDispatcher\Event;

class GetNextSong extends Event
{
    const NAME = 'autodj-next-song';

    /** @var null|Entity\StationMedia The next song, if it's already calculated. */
    protected $next_song;

    /** @var array Custom annotations that should be sent along with the AutoDJ response. */
    protected $annotations = [];

    /** @var Entity\Station */
    protected $station;

    public function __construct(Entity\Station $station)
    {
        $this->station = $station;
    }

    /**
     * @return Entity\StationMedia|null
     */
    public function getNextSong(): ?Entity\StationMedia
    {
        return $this->next_song;
    }

    /**
     * @param Entity\StationMedia|null $next_song
     */
    public function setNextSong(?Entity\StationMedia $next_song): void
    {
        $this->next_song = $next_song;
    }

    /**
     * @return bool
     */
    public function hasNextSong(): bool
    {
        return ($this->next_song instanceof Entity\StationMedia);
    }

    /**
     * @param array $annotations
     */
    public function setAnnotations(array $annotations): void
    {
        $this->annotations = $annotations;
    }

    /**
     * @param array $annotations
     */
    public function addAnnotations(array $annotations): void
    {
        $this->annotations = array_merge($this->annotations, $annotations);
    }

    /**
     * @return Entity\Station
     */
    public function getStation(): Entity\Station
    {
        return $this->station;
    }

    /**
     * @param Entity\Station $station
     */
    public function setStation(Entity\Station $station): void
    {
        $this->station = $station;
    }



}
