<?php

namespace App\Event\Media;

use App\Entity;
use Symfony\Contracts\EventDispatcher\Event;

class WriteMetadata extends Event
{
    protected Entity\Metadata $metadata;

    protected string $path;

    public function __construct(Entity\Metadata $metadata, string $path)
    {
        $this->metadata = $metadata;
        $this->path = $path;
    }

    public function getMetadata(): ?Entity\Metadata
    {
        return $this->metadata;
    }

    public function getPath(): string
    {
        return $this->path;
    }
}
