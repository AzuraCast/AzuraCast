<?php

declare(strict_types=1);

namespace App\Entity;

use App\Annotations\AuditLog;
use App\Entity\Traits;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="podcast_media")
 * @ORM\Entity()
 *
 * @AuditLog\Auditable
 */
class PodcastMedia
{
    use Traits\TruncateStrings;

    public const DIR_PODCAST_MEDIA_ARTWORK = '.podcast_media_art';

    /**
     * @ORM\Id
     * @ORM\Column(name="id", type="guid", unique=true)
     * @ORM\GeneratedValue(strategy="UUID")
     *
     * @var string|null
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="StorageLocation")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="storage_location_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     *
     * @var StorageLocation
     */
    protected $storage_location;

    /**
     * @ORM\OneToOne(targetEntity="PodcastEpisode", inversedBy="media")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="episode_id", referencedColumnName="id", onDelete="SET NULL")
     * })
     *
     * @var PodcastEpisode|null
     */
    protected $episode;

    /**
     * @ORM\Column(name="original_name", type="string", length=200)
     *
     * @Assert\NotBlank
     *
     * @var string The original name of the podcast media file.
     */
    protected $original_name;

    /**
     * @ORM\Column(name="length", type="decimal", precision=7, scale=2)
     *
     * @var float The podcast media's duration in seconds.
     */
    protected $length = 0.00;

    /**
     * @ORM\Column(name="length_text", type="string", length=10)
     *
     * @var string The formatted podcast media's duration (in mm:ss format)
     */
    protected $length_text = '0:00';

    /**
     * @ORM\Column(name="path", type="string", length=500)
     *
     * @Assert\NotBlank
     *
     * @var string The relative path of the podcast media file.
     */
    protected $path;

    /**
     * @ORM\Column(name="mime_type", type="string", length=255)
     *
     * @Assert\NotBlank
     *
     * @var string The mime type of the podcast media file.
     */
    protected $mime_type = 'application/octet-stream';

    /**
     * @ORM\Column(name="modified_time", type="integer")
     *
     * @var int Timestamp of when the podcast media was last modified
     */
    protected $modified_time = 0;

    /**
     * @ORM\Column(name="art_updated_at", type="integer")
     * @AuditLog\AuditIgnore()
     *
     * @var int The latest time (UNIX timestamp) when album art was updated.
     */
    protected $art_updated_at = 0;

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

    public static function getArtPath(string $uniqueId): string
    {
        return self::DIR_PODCAST_MEDIA_ARTWORK . '/' . $uniqueId . '.jpg';
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
