<?php

declare(strict_types=1);

namespace App\Entity;

use App\Annotations\AuditLog;
use App\Entity\Traits;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: 'podcast_media')]
#[ORM\Entity]
#[AuditLog\Auditable]
class PodcastMedia
{
    use Traits\TruncateStrings;

    #[ORM\Column(type: 'guid', unique: true, nullable: false)]
    #[ORM\Id, ORM\GeneratedValue(strategy: 'UUID')]
    protected ?string $id;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'storage_location_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected StorageLocation $storage_location;

    #[ORM\OneToOne(inversedBy: 'media')]
    #[ORM\JoinColumn(name: 'episode_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
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
    #[AuditLog\AuditIgnore]
    protected int $art_updated_at = 0;

    public function __construct(StorageLocation $storageLocation)
    {
        $this->storage_location = $storageLocation;
    }

    public function getId(): ?string
    {
        return $this->id;
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
        return (float)$this->length;
    }

    public function setLength(float $length): self
    {
        $lengthMin = floor($length / 60);
        $lengthSec = $length % 60;

        $this->length = (float)$length;
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

    public function setArtUpdatedAt(int $art_updated_at): self
    {
        $this->art_updated_at = $art_updated_at;

        return $this;
    }

    /**
     * @param string|float|null $seconds
     */
    protected function parseSeconds($seconds = null): ?float
    {
        if ($seconds === '') {
            return null;
        }

        if (false !== strpos($seconds, ':')) {
            $sec = 0;
            foreach (array_reverse(explode(':', $seconds)) as $k => $v) {
                $sec += (60 ** (int)$k) * (int)$v;
            }

            return $sec;
        }

        return $seconds;
    }
}
