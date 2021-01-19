<?php

namespace App\Event\Media;

use App\Entity;
use Symfony\Contracts\EventDispatcher\Event;

class GetAlbumArt extends Event
{
    protected Entity\SongInterface $song;

    protected ?string $albumArt = null;

    public function __construct(Entity\SongInterface $song)
    {
        $this->song = $song;
    }

    public function getSong(): Entity\SongInterface
    {
        return $this->song;
    }

    public function setAlbumArt(?string $albumArt): void
    {
        $this->albumArt = !empty($albumArt)
            ? $albumArt
            : null;

        if (null !== $this->albumArt) {
            $this->stopPropagation();
        }
    }

    public function getAlbumArt(): ?string
    {
        return $this->albumArt;
    }
}
