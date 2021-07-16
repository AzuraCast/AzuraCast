<?php

declare(strict_types=1);

namespace App\Event\Media;

use App\Entity;
use Symfony\Contracts\EventDispatcher\Event;

class GetAlbumArt extends Event
{
    protected ?string $albumArt = null;

    public function __construct(
        protected Entity\Interfaces\SongInterface $song
    ) {
    }

    public function getSong(): Entity\Interfaces\SongInterface
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
