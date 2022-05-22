<?php

declare(strict_types=1);

namespace App\Media;

final class Metadata implements MetadataInterface
{
    /** @var array<string, mixed> */
    private array $tags = [];

    private float $duration = 0.0;

    private ?string $artwork = null;

    private string $mimeType = '';

    public function getTags(): array
    {
        return $this->tags;
    }

    public function setTags(array $tags): void
    {
        $this->tags = $tags;
    }

    public function addTag(string $key, mixed $value): void
    {
        $this->tags[$key] = $value;
    }

    public function getDuration(): float
    {
        return $this->duration;
    }

    public function setDuration(float $duration): void
    {
        $this->duration = $duration;
    }

    public function getArtwork(): ?string
    {
        return $this->artwork;
    }

    public function setArtwork(?string $artwork): void
    {
        $this->artwork = $artwork;
    }

    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    public function setMimeType(string $mimeType): void
    {
        $this->mimeType = $mimeType;
    }
}
