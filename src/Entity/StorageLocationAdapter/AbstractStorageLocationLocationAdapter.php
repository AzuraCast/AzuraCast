<?php

declare(strict_types=1);

namespace App\Entity\StorageLocationAdapter;

use App\Entity\StorageLocation;
use App\Flysystem\ExtendedFilesystemInterface;
use App\Flysystem\RemoteFilesystem;

abstract class AbstractStorageLocationLocationAdapter implements StorageLocationAdapterInterface
{
    public function __construct(
        protected readonly StorageLocation $storageLocation
    ) {
        if ($this->getType() !== $storageLocation->getAdapterEnum()) {
            throw new \InvalidArgumentException('This storage location is not using the specified adapter.');
        }
    }

    public static function filterPath(string $path): string
    {
        return rtrim($path, '/');
    }

    public function getUri(?string $suffix = null): string
    {
        return $this->applyPath($suffix);
    }

    protected function applyPath(?string $suffix = null): string
    {
        $suffix = (null !== $suffix)
            ? '/' . ltrim($suffix, '/')
            : '';

        return $this->storageLocation->getPath() . $suffix;
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
}
