<?php

namespace App\Event\Media;

use App\Entity;
use Symfony\Contracts\EventDispatcher\Event;

class ReadMetadata extends Event
{
    protected string $path;

    protected ?Entity\Metadata $metadata = null;

    public function __construct(string $path)
    {
        $this->path = $path;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function setMetadata(Entity\Metadata $metadata): void
    {
        $this->metadata = $metadata;
    }

    public function getMetadata(): Entity\Metadata
    {
        return $this->metadata ?? new Entity\Metadata();
    }
}
