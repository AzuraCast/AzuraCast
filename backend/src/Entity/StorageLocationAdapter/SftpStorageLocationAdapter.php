<?php

declare(strict_types=1);

namespace App\Entity\StorageLocationAdapter;

use App\Entity\Enums\StorageLocationAdapters;
use App\Flysystem\Adapter\ExtendedAdapterInterface;
use App\Flysystem\Adapter\SftpAdapter;
use League\Flysystem\PhpseclibV3\SftpConnectionProvider;

final class SftpStorageLocationAdapter extends AbstractStorageLocationLocationAdapter
{
    public function getType(): StorageLocationAdapters
    {
        return StorageLocationAdapters::Sftp;
    }

    public function getStorageAdapter(): ExtendedAdapterInterface
    {
        $filteredPath = self::filterPath($this->storageLocation->path);
        return new SftpAdapter($this->getSftpConnectionProvider(), $filteredPath);
    }

    private function getSftpConnectionProvider(): SftpConnectionProvider
    {
        return new SftpConnectionProvider(
            $this->storageLocation->sftpHost ?? '',
            $this->storageLocation->sftpUsername ?? '',
            $this->storageLocation->sftpPassword,
            $this->storageLocation->sftpPrivateKey,
            $this->storageLocation->sftpPrivateKeyPassPhrase,
            $this->storageLocation->sftpPort ?? 22
        );
    }
}
