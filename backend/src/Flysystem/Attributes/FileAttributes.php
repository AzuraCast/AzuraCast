<?php

declare(strict_types=1);

namespace App\Flysystem\Attributes;

use League\Flysystem\StorageAttributes;
use League\Flysystem\UnableToRetrieveMetadata;

final class FileAttributes extends AbstractAttributes
{
    /**
     * @param string $path
     * @param int|callable|null $fileSize
     * @param string|callable|null $visibility
     * @param int|callable|null $lastModified
     * @param string|callable|null $mimeType
     * @param array $extraMetadata
     */
    public function __construct(
        string $path,
        private $fileSize = null,
        $visibility = null,
        $lastModified = null,
        private $mimeType = null,
        array $extraMetadata = []
    ) {
        $this->type = StorageAttributes::TYPE_FILE;

        parent::__construct($path, $visibility, $lastModified, $extraMetadata);
    }

    public function fileSize(): ?int
    {
        $fileSize = is_callable($this->fileSize)
            ? ($this->fileSize)($this->path)
            : $this->fileSize;

        if (null === $fileSize) {
            throw UnableToRetrieveMetadata::fileSize($this->path);
        }

        return $fileSize;
    }

    public function mimeType(): ?string
    {
        $mimeType = is_callable($this->mimeType)
            ? ($this->mimeType)($this->path)
            : $this->mimeType;

        if (null === $mimeType) {
            throw UnableToRetrieveMetadata::mimeType($this->path);
        }

        return $mimeType;
    }

    public static function fromArray(array $attributes): self
    {
        return new self(
            $attributes[StorageAttributes::ATTRIBUTE_PATH],
            $attributes[StorageAttributes::ATTRIBUTE_FILE_SIZE] ?? null,
            $attributes[StorageAttributes::ATTRIBUTE_VISIBILITY] ?? null,
            $attributes[StorageAttributes::ATTRIBUTE_LAST_MODIFIED] ?? null,
            $attributes[StorageAttributes::ATTRIBUTE_MIME_TYPE] ?? null,
            $attributes[StorageAttributes::ATTRIBUTE_EXTRA_METADATA] ?? []
        );
    }

    public function jsonSerialize(): array
    {
        return [
            StorageAttributes::ATTRIBUTE_TYPE => self::TYPE_FILE,
            StorageAttributes::ATTRIBUTE_PATH => $this->path,
            StorageAttributes::ATTRIBUTE_FILE_SIZE => $this->fileSize,
            StorageAttributes::ATTRIBUTE_VISIBILITY => $this->visibility,
            StorageAttributes::ATTRIBUTE_LAST_MODIFIED => $this->lastModified,
            StorageAttributes::ATTRIBUTE_MIME_TYPE => $this->mimeType,
            StorageAttributes::ATTRIBUTE_EXTRA_METADATA => $this->extraMetadata,
        ];
    }
}
