<?php

declare(strict_types=1);

namespace App\Event\Media;

use Symfony\Contracts\EventDispatcher\Event;
use App\Entity\Interfaces\SongInterface;

final class GetAlbumArt extends Event
{
    private ?string $albumArt = null;

    public function __construct(
        private readonly SongInterface $song
    ) {
    }

    public function getSong(): SongInterface
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
