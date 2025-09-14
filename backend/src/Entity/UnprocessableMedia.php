<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Interfaces\IdentifiableEntityInterface;
use App\Entity\Interfaces\PathAwareInterface;
use Doctrine\ORM\Mapping as ORM;

#[
    ORM\Entity,
    ORM\Table(name: 'unprocessable_media'),
    ORM\UniqueConstraint(name: 'path_unique_idx', columns: ['path', 'storage_location_id'])
]
final class UnprocessableMedia implements PathAwareInterface, IdentifiableEntityInterface
{
    use Traits\HasAutoIncrementId;
    use Traits\TruncateStrings;

    public const int REPROCESS_THRESHOLD_MINIMUM = 604800; // One week

    #[ORM\ManyToOne(inversedBy: 'unprocessable_media')]
    #[ORM\JoinColumn(name: 'storage_location_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    public readonly StorageLocation $storage_location;

    /* TODO Remove direct identifier access. */
    #[ORM\Column(nullable: false, insertable: false, updatable: false)]
    public private(set) int $storage_location_id;

    #[ORM\Column(length: 500)]
    public string $path {
        get => $this->path;
        set => $this->truncateString($value, 500);
    }

    #[ORM\Column(nullable: false)]
    public int $mtime = 0;

    #[ORM\Column(type: 'text', nullable: true)]
    public ?string $error = null;

    public function __construct(StorageLocation $storageLocation, string $path)
    {
        $this->storage_location = $storageLocation;
        $this->path = $path;
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
