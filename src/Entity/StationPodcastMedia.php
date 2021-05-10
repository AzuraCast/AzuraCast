<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Traits;
use Doctrine\ORM\Mapping as ORM;
use Plugin\AzuraCastPodcastPlugin\Flysystem\Filesystem;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="station_podcast_media")
 * @ORM\Entity()
 */
class StationPodcastMedia
{
    use Traits\UniqueId, Traits\TruncateStrings;

    public const UNIQUE_ID_LENGTH = 24;

    public const DIR_PODCAST_MEDIA_ARTWORK = '.podcast_media_art';

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     *
     * @var int|null
     */
    protected $id;

    /**
     * @ORM\Column(name="station_id", type="integer")
     *
     * @var int
     */
    protected $stationId;

    /**
     * @ORM\ManyToOne(targetEntity="Station")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="station_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     *
     * @var Station
     */
    protected $station;

    /**
     * @ORM\Column(name="episode_id", type="integer", nullable=true)
     *
     * @var int|null
     */
    protected $episodeId;

    /**
     * @ORM\OneToOne(targetEntity="StationPodcastEpisode", inversedBy="podcastMedia")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="episode_id", referencedColumnName="id", onDelete="SET NULL")
     * })
     *
     * @var StationPodcastEpisode|null
     */
    protected $episode;

    /**
     * @ORM\Column(name="original_name", type="string", length=200)
     *
     * @Assert\NotBlank
     *
     * @var string The original name of the podcast media file.
     */
    protected $originalName;

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
    protected $lengthText = '0:00';

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
    protected $mimeType;

    /**
     * @ORM\Column(name="modified_time", type="integer")
     *
     * @var int Timestamp of when the podcast media was last modified
     */
    protected $modifiedTime;

    public function __construct(Station $station)
    {
        $this->station = $station;

        $this->generateUniqueId();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStation(): Station
    {
        return $this->station;
    }

    public function getEpisode(): ?StationPodcastEpisode
    {
        return $this->episode;
    }

    public function setEpisode(?StationPodcastEpisode $episode): self
    {
        $this->episode = $episode;

        return $this;
    }

    public function getOriginalName(): string
    {
        return $this->originalName;
    }

    public function setOriginalName(string $originalName): self
    {
        $this->originalName = $this->truncateString($originalName);

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
        $this->lengthText = $lengthMin . ':' . str_pad((string)$lengthSec, 2, '0', STR_PAD_LEFT);

        return $this;
    }

    public function getLengthText(): string
    {
        return $this->lengthText;
    }

    public function setLengthText(string $lengthText): self
    {
        $this->lengthText = $lengthText;

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
        return $this->mimeType;
    }

    public function setMimeType(string $mimeType): self
    {
        $this->mimeType = $mimeType;

        return $this;
    }

    public function getModifiedTime(): int
    {
        return $this->modifiedTime;
    }

    public function setModifiedTime(int $modifiedTime): self
    {
        $this->modifiedTime = $modifiedTime;

        return $this;
    }

    public static function getArtworkPath(string $uniqueId): string
    {
        return self::DIR_PODCAST_MEDIA_ARTWORK . '/' . $uniqueId . '.jpg';
    }

    /**
     * @param string|float|null $seconds
     *
     * @return float|null
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
