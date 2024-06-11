<?php

declare(strict_types=1);

namespace App\Flysystem\Attributes;

use League\Flysystem\StorageAttributes;

final class DirectoryAttributes extends AbstractAttributes
{
    /**
     * @param string $path
     * @param string|callable|null $visibility
     * @param int|callable|null $lastModified
     * @param array $extraMetadata
     */
    public function __construct(string $path, $visibility = null, $lastModified = null, array $extraMetadata = [])
    {
        $this->type = StorageAttributes::TYPE_DIRECTORY;
        parent::__construct($path, $visibility, $lastModified, $extraMetadata);
    }

    public static function fromArray(array $attributes): self
    {
        return new self(
            $attributes[StorageAttributes::ATTRIBUTE_PATH],
            $attributes[StorageAttributes::ATTRIBUTE_VISIBILITY] ?? null,
            $attributes[StorageAttributes::ATTRIBUTE_LAST_MODIFIED] ?? null,
            $attributes[StorageAttributes::ATTRIBUTE_EXTRA_METADATA] ?? [],
        );
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): array
    {
        return [
            StorageAttributes::ATTRIBUTE_TYPE => $this->type,
            StorageAttributes::ATTRIBUTE_PATH => $this->path,
            StorageAttributes::ATTRIBUTE_VISIBILITY => $this->visibility,
            StorageAttributes::ATTRIBUTE_LAST_MODIFIED => $this->lastModified,
            StorageAttributes::ATTRIBUTE_EXTRA_METADATA => $this->extraMetadata,
        ];
    }
}
