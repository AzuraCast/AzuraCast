<?php

declare(strict_types=1);

namespace App\Event\Media;

use App\Entity;
use Symfony\Contracts\EventDispatcher\Event;

final class GetAlbumArt extends Event
{
    private ?string $albumArt = null;

    public function __construct(
        private readonly Entity\Interfaces\SongInterface $song
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
