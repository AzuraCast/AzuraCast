<?php

declare(strict_types=1);

namespace App\Media;

interface MetadataInterface
{
    /**
     * @return array<string, mixed>
     */
    public function getTags(): array;

    /**
     * @param array<string, mixed> $tags
     */
    public function setTags(array $tags): void;

    /**
     * @param string $key
     * @param mixed $value
     */
    public function addTag(string $key, mixed $value): void;

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
