<?php

declare(strict_types=1);

namespace App\Entity\StorageLocationAdapter;

use App\Entity\StorageLocation;
use App\Flysystem\ExtendedFilesystemInterface;
use App\Flysystem\RemoteFilesystem;
use InvalidArgumentException;

abstract class AbstractStorageLocationLocationAdapter implements StorageLocationAdapterInterface
{
    protected StorageLocation $storageLocation;

    public function withStorageLocation(StorageLocation $storageLocation): static
    {
        $clone = clone $this;
        $clone->setStorageLocation($storageLocation);
        return $clone;
    }

    protected function setStorageLocation(StorageLocation $storageLocation): void
    {
        if ($this->getType() !== $storageLocation->getAdapter()) {
            throw new InvalidArgumentException('This storage location is not using the specified adapter.');
        }

        $this->storageLocation = $storageLocation;
    }

    public function getFilesystem(): ExtendedFilesystemInterface
    {
        return new RemoteFilesystem($this->getStorageAdapter());
    }

    public function validate(): void
    {
        $adapter = $this->getStorageAdapter();
        $adapter->fileExists('/test');
    }

    public static function filterPath(string $path): string
    {
        return rtrim($path, '/');
    }

    public static function getUri(
        StorageLocation $storageLocation,
        ?string $suffix = null
    ): string {
        return self::applyPath($storageLocation->getPath(), $suffix);
    }

    protected static function applyPath(
        string $path,
        ?string $suffix = null
    ): string {
        $suffix = (null !== $suffix)
            ? '/' . ltrim($suffix, '/')
            : '';

        return $path . $suffix;
    }
}
