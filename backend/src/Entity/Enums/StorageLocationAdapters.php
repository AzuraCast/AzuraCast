<?php

declare(strict_types=1);

namespace App\Entity\Enums;

use App\Entity\StorageLocationAdapter\DropboxStorageLocationAdapter;
use App\Entity\StorageLocationAdapter\LocalStorageLocationAdapter;
use App\Entity\StorageLocationAdapter\S3StorageLocationAdapter;
use App\Entity\StorageLocationAdapter\SftpStorageLocationAdapter;
use App\Entity\StorageLocationAdapter\StorageLocationAdapterInterface;

enum StorageLocationAdapters: string
{
    case Local = 'local';
    case S3 = 's3';
    case Dropbox = 'dropbox';
    case Sftp = 'sftp';

    public function isLocal(): bool
    {
        return self::Local === $this;
    }

    public function getName(): string
    {
        return match ($this) {
            self::Local => 'Local',
            self::S3 => 'S3',
            self::Dropbox => 'Dropbox',
            self::Sftp => 'SFTP',
        };
    }

    /**
     * @return class-string<StorageLocationAdapterInterface>
     */
    public function getAdapterClass(): string
    {
        return match ($this) {
            self::Local => LocalStorageLocationAdapter::class,
            self::S3 => S3StorageLocationAdapter::class,
            self::Dropbox => DropboxStorageLocationAdapter::class,
            self::Sftp => SftpStorageLocationAdapter::class
        };
    }
}
