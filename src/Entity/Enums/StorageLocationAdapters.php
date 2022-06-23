<?php

declare(strict_types=1);

namespace App\Entity\Enums;

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
}
