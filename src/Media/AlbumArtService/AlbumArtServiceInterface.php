<?php

namespace App\Media\AlbumArtService;

use App\Entity;

interface AlbumArtServiceInterface
{
    public function isSupported(): bool;

    public function getAlbumArt(Entity\SongInterface $song): ?string;
}
