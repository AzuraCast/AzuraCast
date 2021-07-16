<?php

declare(strict_types=1);

namespace App\Event\Media;

use App\Entity;
use Symfony\Contracts\EventDispatcher\Event;

class ReadMetadata extends Event
{
    protected ?Entity\Metadata $metadata = null;

    public function __construct(
        protected string $path
    ) {
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
