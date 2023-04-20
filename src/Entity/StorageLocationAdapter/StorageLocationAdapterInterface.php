<?php

declare(strict_types=1);

namespace App\Entity\StorageLocationAdapter;

use App\Entity\Enums\StorageLocationAdapters;
use App\Entity\StorageLocation;
use App\Flysystem\Adapter\ExtendedAdapterInterface;
use App\Flysystem\ExtendedFilesystemInterface;

interface StorageLocationAdapterInterface
{
    public function withStorageLocation(StorageLocation $storageLocation): static;

    public function getType(): StorageLocationAdapters;

    public function getStorageAdapter(): ExtendedAdapterInterface;

    public function getFilesystem(): ExtendedFilesystemInterface;

    public function validate(): void;

    public static function filterPath(string $path): string;

    public static function getUri(StorageLocation $storageLocation, ?string $suffix = null): string;
}
