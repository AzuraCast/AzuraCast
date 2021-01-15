<?php

namespace App\Event\Radio;

use App\Entity;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\UriInterface;
use Symfony\Contracts\EventDispatcher\Event;

class GetAlbumArt extends Event
{
    protected Entity\SongInterface $song;

    protected ?UriInterface $albumArt = null;

    public function __construct(Entity\SongInterface $song)
    {
        $this->song = $song;
    }

    public function getSong(): Entity\SongInterface
    {
        return $this->song;
    }

    /**
     * @param string|UriInterface $albumArt
     */
    public function setAlbumArt($albumArt): void
    {
        if (!($albumArt instanceof UriInterface)) {
            $albumArt = new Uri($albumArt);
        }

        $this->albumArt = $albumArt;
        $this->stopPropagation();
    }

    public function getAlbumArt(): ?UriInterface
    {
        return $this->albumArt;
    }
}
