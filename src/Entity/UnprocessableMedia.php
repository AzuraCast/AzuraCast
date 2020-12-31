<?php

/** @noinspection PhpMissingFieldTypeInspection */

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="unprocessable_media", uniqueConstraints={
 *   @ORM\UniqueConstraint(name="path_unique_idx", columns={"path", "storage_location_id"})
 * })
 * @ORM\Entity()
 */
class UnprocessableMedia implements ProcessableMediaInterface, PathAwareInterface
{
    public const REPROCESS_THRESHOLD_MINIMUM = 604800; // One week

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     *
     * @var int|null
     */
    protected $id;

    /**
     * @ORM\Column(name="storage_location_id", type="integer")
     * @var int
     */
    protected $storage_location_id;

    /**
     * @ORM\ManyToOne(targetEntity="StorageLocation", inversedBy="media")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="storage_location_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     * @var StorageLocation
     */
    protected $storage_location;

    /**
     * @ORM\Column(name="path", type="string", length=500)
     *
     * @var string The relative path of the media file.
     */
    protected $path;

    /**
     * @ORM\Column(name="mtime", type="integer", nullable=true)
     *
     * @var int|null The UNIX timestamp when the database was last modified.
     */
    protected $mtime = 0;

    /**
     * @ORM\Column(name="error", type="text", nullable=true)
     *
     * @var string|null The full text of any errors that occurred during processing.
     */
    protected $error;

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

    public function needsReprocessing(int $currentFileModifiedTime = 0): bool
    {
        if ($currentFileModifiedTime > $this->mtime) {
            return true;
        }

        $threshold = $this->mtime + self::REPROCESS_THRESHOLD_MINIMUM + random_int(0, 86400);
        return time() > $threshold;
    }
}
