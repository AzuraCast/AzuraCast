<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;

class Metadata
{
    protected ArrayCollection $tags;

    protected float $duration = 0.0;

    protected ?string $artwork = null;

    protected string $mimeType = '';

    public function __construct()
    {
        $this->tags = new ArrayCollection();
    }

    public function getTags(): ArrayCollection
    {
        return $this->tags;
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
