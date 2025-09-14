<?php

declare(strict_types=1);

namespace App\Media;

use App\Media\Enums\MetadataTags;

/**
 * @phpstan-type KnownTags array<value-of<MetadataTags>, mixed>
 * @phpstan-type ExtraTags array<string, mixed>
 */
interface MetadataInterface
{
    /**
     * @return KnownTags
     */
    public function getKnownTags(): array;

    /**
     * @param KnownTags $tags
     */
    public function setKnownTags(array $tags): void;

    /**
     * @return ExtraTags
     */
    public function getExtraTags(): array;

    /**
     * @param ExtraTags $tags
     */
    public function setExtraTags(array $tags): void;

    /**
     * @return float
     */
    public function getDuration(): float;

    /**
     * @param float $duration
     */
    public function setDuration(float $duration): void;

    /**
     * @return string|null
     */
    public function getArtwork(): ?string;

    public function setArtwork(?string $artwork): void;

    public function getMimeType(): string;

    public function setMimeType(string $mimeType): void;
}
