<?php

declare(strict_types=1);

namespace App\Media;

/**
 * @phpstan-import-type KnownTags from MetadataInterface
 * @phpstan-import-type ExtraTags from MetadataInterface
 */
final class Metadata implements MetadataInterface
{
    public const string MULTI_VALUE_SEPARATOR = ';';

    /** @var KnownTags */
    private array $knownTags = [];

    /** @var ExtraTags */
    private array $extraTags = [];

    private float $duration = 0.0;

    private ?string $artwork = null;

    private bool $removeArtwork = false;

    private string $mimeType = '';

    public function getKnownTags(): array
    {
        return $this->knownTags;
    }

    public function setKnownTags(array $tags): void
    {
        $this->knownTags = $tags;
    }

    public function getExtraTags(): array
    {
        return $this->extraTags;
    }

    public function setExtraTags(array $tags): void
    {
        $this->extraTags = $tags;
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
        $this->removeArtwork = false;
    }

    public function removeArtwork(): void
    {
        $this->artwork = null;
        $this->removeArtwork = true;
    }

    public function shouldRemoveArtwork(): bool
    {
        return $this->removeArtwork;
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
