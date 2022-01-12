<?php

declare(strict_types=1);

namespace App\Event\Media;

use Azura\MetadataManager\MetadataInterface;
use Symfony\Contracts\EventDispatcher\Event;

class WriteMetadata extends Event
{
    public function __construct(
        protected MetadataInterface $metadata,
        protected string $path
    ) {
    }

    public function getMetadata(): ?MetadataInterface
    {
        return $this->metadata;
    }

    public function getPath(): string
    {
        return $this->path;
    }
}
