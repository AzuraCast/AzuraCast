<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Interfaces\IdentifiableEntityInterface;
use App\Entity\Traits;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[
    ORM\Entity,
    ORM\Table(name: 'podcast_media'),
    Attributes\Auditable
]
class PodcastMedia implements IdentifiableEntityInterface
{
    use Traits\HasUniqueId;
    use Traits\TruncateStrings;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'storage_location_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected StorageLocation $storage_location;

    #[ORM\OneToOne(inversedBy: 'media')]
    #[ORM\JoinColumn(name: 'episode_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    protected ?PodcastEpisode $episode;

    #[ORM\Column(length: 200)]
    #[Assert\NotBlank]
    protected string $original_name;

    #[ORM\Column(type: 'decimal', precision: 7, scale: 2)]
    protected float $length = 0.00;

    /** @var string The formatted podcast media's duration (in mm:ss format) */
    #[ORM\Column(length: 10)]
    protected string $length_text = '0:00';

    #[ORM\Column(length: 500)]
    #[Assert\NotBlank]
    protected string $path;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    protected string $mime_type = 'application/octet-stream';

    #[ORM\Column]
    protected int $modified_time = 0;

    #[ORM\Column]
    #[Attributes\AuditIgnore]
    protected int $art_updated_at = 0;

    public function __construct(StorageLocation $storageLocation)
    {
        $this->storage_location = $storageLocation;
    }

    public function getStorageLocation(): StorageLocation
    {
        return $this->storage_location;
    }

    public function getEpisode(): ?PodcastEpisode
    {
        return $this->episode;
    }

    public function setEpisode(?PodcastEpisode $episode): self
    {
        $this->episode = $episode;

        return $this;
    }

    public function getOriginalName(): string
    {
        return $this->original_name;
    }

    public function setOriginalName(string $originalName): self
    {
        $this->original_name = $this->truncateString($originalName);

        return $this;
    }

    public function getLength(): float
    {
        return $this->length;
    }

    public function setLength(float $length): self
    {
        $lengthMin = floor($length / 60);
        $lengthSec = (int)$length % 60;

        $this->length = $length;
        $this->length_text = $lengthMin . ':' . str_pad((string)$lengthSec, 2, '0', STR_PAD_LEFT);

        return $this;
    }

    public function getLengthText(): string
    {
        return $this->length_text;
    }

    public function setLengthText(string $lengthText): self
    {
        $this->length_text = $lengthText;

        return $this;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function setPath(string $path): self
    {
        $this->path = $path;

        return $this;
    }

    public function getMimeType(): string
    {
        return $this->mime_type;
    }

    public function setMimeType(string $mimeType): self
    {
        $this->mime_type = $mimeType;

        return $this;
    }

    public function getModifiedTime(): int
    {
        return $this->modified_time;
    }

    public function setModifiedTime(int $modifiedTime): self
    {
        $this->modified_time = $modifiedTime;

        return $this;
    }

    public function getArtUpdatedAt(): int
    {
        return $this->art_updated_at;
    }

    public function setArtUpdatedAt(int $artUpdatedAt): self
    {
        $this->art_updated_at = $artUpdatedAt;

        return $this;
    }
}
