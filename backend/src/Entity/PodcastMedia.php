<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Interfaces\IdentifiableEntityInterface;
use App\Utilities\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[
    ORM\Entity,
    ORM\Table(name: 'podcast_media'),
    Attributes\Auditable
]
final class PodcastMedia implements IdentifiableEntityInterface
{
    use Traits\HasUniqueId;
    use Traits\TruncateStrings;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'storage_location_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    public readonly StorageLocation $storage_location;

    #[ORM\OneToOne(inversedBy: 'media')]
    #[ORM\JoinColumn(name: 'episode_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    public ?PodcastEpisode $episode;

    #[ORM\Column(length: 200)]
    #[Assert\NotBlank]
    public string $original_name {
        set => $this->truncateString($value, 200);
    }

    #[ORM\Column(name: 'length', type: 'decimal', precision: 7, scale: 2)]
    private string $length_str = '0.0';

    public float $length {
        get => Types::float($this->length_str);
        set {
            $lengthMin = floor($value / 60);
            $lengthSec = (int)$value % 60;

            $this->length_str = (string)$value;
            $this->length_text = $lengthMin . ':' .
                str_pad((string)$lengthSec, 2, '0', STR_PAD_LEFT);
        }
    }

    /** @var string The formatted podcast media's duration (in mm:ss format) */
    #[ORM\Column(length: 10)]
    public string $length_text = '0:00';

    #[ORM\Column(length: 500)]
    #[Assert\NotBlank]
    public string $path;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    public string $mime_type = 'application/octet-stream';

    #[ORM\Column]
    public int $modified_time = 0;

    #[ORM\Column]
    #[Attributes\AuditIgnore]
    public int $art_updated_at = 0;

    public function __construct(StorageLocation $storageLocation)
    {
        $this->storage_location = $storageLocation;
    }
}
