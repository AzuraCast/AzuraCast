<?php

declare(strict_types=1);

namespace App\Entity\StorageLocationAdapter;

use App\Entity\Enums\StorageLocationAdapters;
use App\Flysystem\Adapter\ExtendedAdapterInterface;
use App\Flysystem\ExtendedFilesystemInterface;

interface StorageLocationAdapterInterface
{
    public function getType(): StorageLocationAdapters;

    public function getUri(?string $suffix = null): string;

    public function getStorageAdapter(): ExtendedAdapterInterface;

    public function getFilesystem(): ExtendedFilesystemInterface;

    public function validate(): void;

    public static function filterPath(string $path): string;
}
