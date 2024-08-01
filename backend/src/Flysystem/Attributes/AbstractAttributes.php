<?php

declare(strict_types=1);

namespace App\Flysystem\Attributes;

use League\Flysystem\ProxyArrayAccessToProperties;
use League\Flysystem\StorageAttributes;
use League\Flysystem\UnableToRetrieveMetadata;

abstract class AbstractAttributes implements StorageAttributes
{
    use ProxyArrayAccessToProperties;

    protected string $type;

    /**
     * @param string $path
     * @param string|callable|null $visibility
     * @param int|callable|null $lastModified
     * @param array $extraMetadata
     */
    public function __construct(
        protected string $path,
        protected $visibility = null,
        protected $lastModified = null,
        protected array $extraMetadata = []
    ) {
    }

    public function path(): string
    {
        return $this->path;
    }

    public function type(): string
    {
        return $this->type;
    }

    public function visibility(): ?string
    {
        return (is_callable($this->visibility))
            ? ($this->visibility)($this->path)
            : $this->visibility;
    }

    public function lastModified(): ?int
    {
        $lastModified = is_callable($this->lastModified)
            ? ($this->lastModified)($this->path)
            : $this->lastModified;

        if (null === $lastModified) {
            throw UnableToRetrieveMetadata::lastModified($this->path);
        }

        return $lastModified;
    }

    public function extraMetadata(): array
    {
        return $this->extraMetadata;
    }

    public function isFile(): bool
    {
        return (StorageAttributes::TYPE_FILE === $this->type);
    }

    public function isDir(): bool
    {
        return (StorageAttributes::TYPE_DIRECTORY === $this->type);
    }

    public function withPath(string $path): StorageAttributes
    {
        $clone = clone $this;
        $clone->path = $path;

        return $clone;
    }
}
