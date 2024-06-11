<?php

declare(strict_types=1);

namespace App\Entity\StorageLocationAdapter;

use App\Container\EnvironmentAwareTrait;
use App\Entity\Enums\StorageLocationAdapters;
use App\Flysystem\Adapter\LocalAdapterInterface;
use App\Flysystem\Adapter\LocalFilesystemAdapter;
use App\Flysystem\ExtendedFilesystemInterface;
use App\Flysystem\LocalFilesystem;
use InvalidArgumentException;
use Symfony\Component\Filesystem\Path;

final class LocalStorageLocationAdapter extends AbstractStorageLocationLocationAdapter
{
    use EnvironmentAwareTrait;

    public function getType(): StorageLocationAdapters
    {
        return StorageLocationAdapters::Local;
    }

    public function getStorageAdapter(): LocalAdapterInterface
    {
        $filteredPath = self::filterPath($this->storageLocation->getPath());

        return new LocalFilesystemAdapter($filteredPath);
    }

    public function getFilesystem(): ExtendedFilesystemInterface
    {
        return new LocalFilesystem($this->getStorageAdapter());
    }

    public function validate(): void
    {
        // Check that there is any overlap between the specified path and the docroot.
        $path = $this->storageLocation->getPath();
        $baseDir = $this->environment->getBaseDirectory();

        if (Path::isBasePath($baseDir, $path)) {
            throw new InvalidArgumentException('Directory is within the web root.');
        }

        if (Path::isBasePath($path, $baseDir)) {
            throw new InvalidArgumentException('Directory is a parent directory of the web root.');
        }

        parent::validate();
    }
}
