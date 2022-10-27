<?php

declare(strict_types=1);

namespace App\Entity\StorageLocationAdapter;

use App\Entity\Enums\StorageLocationAdapters;
use Azura\Files\Adapter\Local\LocalFilesystemAdapter;
use Azura\Files\Adapter\LocalAdapterInterface;
use Azura\Files\ExtendedFilesystemInterface;
use Azura\Files\LocalFilesystem;

final class LocalStorageLocationAdapter extends AbstractStorageLocationLocationAdapter
{
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
}
