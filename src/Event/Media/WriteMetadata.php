<?php

declare(strict_types=1);

namespace App\Event\Media;

use App\Entity;
use Symfony\Contracts\EventDispatcher\Event;

class WriteMetadata extends Event
{
    public function __construct(
        protected Entity\Metadata $metadata,
        protected string $path
    ) {
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
