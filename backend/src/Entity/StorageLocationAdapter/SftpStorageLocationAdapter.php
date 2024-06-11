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
        $filteredPath = self::filterPath($this->storageLocation->getPath());
        return new SftpAdapter($this->getSftpConnectionProvider(), $filteredPath);
    }

    private function getSftpConnectionProvider(): SftpConnectionProvider
    {
        return new SftpConnectionProvider(
            $this->storageLocation->getSftpHost() ?? '',
            $this->storageLocation->getSftpUsername() ?? '',
            $this->storageLocation->getSftpPassword(),
            $this->storageLocation->getSftpPrivateKey(),
            $this->storageLocation->getSftpPrivateKeyPassPhrase(),
            $this->storageLocation->getSftpPort() ?? 22
        );
    }
}
