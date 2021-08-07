<?php

declare(strict_types=1);

namespace App\Entity;

class Metadata implements \JsonSerializable
{
    /** @var array<string, mixed> */
    protected array $tags = [];

    protected float $duration = 0.0;

    protected ?string $artwork = null;

    protected string $mimeType = '';

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

    public function jsonSerialize()
    {
        // Artwork is not included in this JSON feed.
        return [
            'tags' => $this->tags,
            'duration' => $this->duration,
            'mimeType' => $this->mimeType,
        ];
    }

    public static function fromJson(array $data): self
    {
        $metadata = new self();

        if (isset($data['tags'])) {
            $metadata->setTags((array)$data['tags']);
        }
        if (isset($data['duration'])) {
            $metadata->setDuration((float)$data['duration']);
        }
        if (isset($data['mimeType'])) {
            $metadata->setMimeType((string)$data['mimeType']);
        }

        return $metadata;
    }
}
