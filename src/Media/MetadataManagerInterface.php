<?php

namespace App\Media;

interface MetadataManagerInterface
{
    /**
     * @param string $path
     *
     */
    public function getMetadata(string $path): Metadata;

    /**
     * @param Metadata $metadata
     * @param string $path
     *
     * @return bool Whether the write operation completed successfully.
     */
    public function writeMetadata(Metadata $metadata, string $path): bool;
}
