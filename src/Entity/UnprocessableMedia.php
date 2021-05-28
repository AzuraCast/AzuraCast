<?php

/** @noinspection PhpMissingFieldTypeInspection */

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[
    ORM\Entity,
    ORM\Table(name: 'unprocessable_media'),
    ORM\UniqueConstraint(name: 'path_unique_idx', columns: ['path', 'storage_location_id'])
]
class UnprocessableMedia implements ProcessableMediaInterface, PathAwareInterface
{
    public const REPROCESS_THRESHOLD_MINIMUM = 604800; // One week

    #[ORM\Column(nullable: false)]
    #[ORM\Id, ORM\GeneratedValue(strategy: 'IDENTITY')]
    protected ?int $id;

    #[ORM\Column]
    protected int $storage_location_id;

    #[ORM\ManyToOne(inversedBy: 'media')]
    #[ORM\JoinColumn(name: 'storage_location_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected StorageLocation $storage_location;

    #[ORM\Column(length: 500)]
    protected string $path;

    #[ORM\Column]
    protected ?int $mtime = 0;

    #[ORM\Column(type: 'text')]
    protected ?string $error;

    public function __construct(StorageLocation $storageLocation, string $path)
    {
        $this->storage_location = $storageLocation;

        $this->setPath($path);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStorageLocation(): StorageLocation
    {
        return $this->storage_location;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function setPath(string $path): void
    {
        $this->path = $path;
    }

    public function getMtime(): ?int
    {
        return $this->mtime;
    }

    public function setMtime(?int $mtime): void
    {
        $this->mtime = $mtime;
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    public function setError(?string $error): void
    {
        $this->error = $error;
    }

    public static function needsReprocessing(int $fileModifiedTime = 0, int $dbModifiedTime = 0): bool
    {
        if ($fileModifiedTime > $dbModifiedTime) {
            return true;
        }

        $threshold = $dbModifiedTime + self::REPROCESS_THRESHOLD_MINIMUM + random_int(0, 86400);
        return time() > $threshold;
    }
}
