<?php

declare(strict_types=1);

namespace App\Event\Media;

use App\Media\Metadata;
use App\Media\MetadataInterface;
use Symfony\Contracts\EventDispatcher\Event;

final class ReadMetadata extends Event
{
    private ?MetadataInterface $metadata = null;

    public function __construct(
        private readonly string $path
    ) {
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function setMetadata(MetadataInterface $metadata): void
    {
        $this->metadata = $metadata;
    }

    public function getMetadata(): MetadataInterface
    {
        return $this->metadata ?? new Metadata();
    }
}
