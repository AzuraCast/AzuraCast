<?php

declare(strict_types=1);

namespace App\Flysystem\Adapter;

use League\Flysystem\FilesystemAdapter;
use League\Flysystem\FilesystemException;
use League\Flysystem\StorageAttributes;
use League\Flysystem\UnableToRetrieveMetadata;

interface ExtendedAdapterInterface extends FilesystemAdapter
{
    /**
     * @throws UnableToRetrieveMetadata
     * @throws FilesystemException
     */
    public function getMetadata(string $path): StorageAttributes;
}
