<?php

namespace App\Flysystem\Adapter;

use League\Flysystem\FilesystemAdapter;
use League\Flysystem\StorageAttributes;

interface AdapterInterface extends FilesystemAdapter
{
    /**
     * @param string $path
     *
     */
    public function getMetadata(string $path): StorageAttributes;
}
