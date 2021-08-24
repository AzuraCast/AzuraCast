<?php

declare(strict_types=1);

namespace App\Event\Media;

use Azura\MetadataManager\Metadata;
use Azura\MetadataManager\MetadataInterface;
use Symfony\Contracts\EventDispatcher\Event;

class ReadMetadata extends Event
{
    protected ?MetadataInterface $metadata = null;

    public function __construct(
        protected string $path
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
