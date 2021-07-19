<?php

declare(strict_types=1);

namespace App\Entity\Interfaces;

interface SongInterface
{
    public function getSongId(): string;

    public function updateSongId(): void;

    public function getText(): ?string;

    public function setText(?string $text): void;

    public function getArtist(): ?string;

    public function setArtist(?string $artist): void;

    public function getTitle(): ?string;

    public function setTitle(?string $title): void;
}
