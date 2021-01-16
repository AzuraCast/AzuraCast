<?php

namespace App\Media\MetadataService;

use App\Entity;

interface MetadataServiceInterface
{
    /**
     * @param string $path
     *
     */
    public function getMetadata(string $path): Entity\Metadata;

    /**
     * @param Entity\Metadata $metadata
     * @param string $path
     *
     * @return bool Whether the write operation completed successfully.
     */
    public function writeMetadata(Entity\Metadata $metadata, string $path): bool;
}
