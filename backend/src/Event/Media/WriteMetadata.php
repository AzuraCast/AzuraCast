<?php

declare(strict_types=1);

namespace App\Event\Media;

use App\Media\MetadataInterface;
use Symfony\Contracts\EventDispatcher\Event;

final class WriteMetadata extends Event
{
    public function __construct(
        private readonly MetadataInterface $metadata,
        private readonly string $path
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
