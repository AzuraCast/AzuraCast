<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Traits;
use Doctrine\ORM\Mapping as ORM;
use Plugin\AzuraCastPodcastPlugin\Flysystem\Filesystem;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="station_podcast_episode")
 * @ORM\Entity
 */
class StationPodcastEpisode
{
    use Traits\UniqueId, Traits\TruncateStrings;

    public const DIR_PODCAST_EPISODE_ARTWORK = '.podcast_episode_art';

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
     * @ORM\Column(name="podcast_id", type="integer")
     *
     * @var int
     */
    protected $podcastId;

    /**
     * @ORM\ManyToOne(targetEntity="StationPodcast", inversedBy="episodes")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="podcast_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     *
     * @var StationPodcast
     */
    protected $podcast;

    /**
     * @ORM\OneToOne(targetEntity="StationPodcastMedia", mappedBy="episode")
     *
     * @var StationPodcastMedia|null
     */
    protected $podcastMedia;

    /**
     * @ORM\Column(name="title", type="string", length=255)
     *
     * @Assert\NotBlank
     *
     * @var string The name of the episode
     */
    protected $title;

    /**
     * @ORM\Column(name="link", type="string", length=255, nullable=true)
     *
     * @var string|null A link to the episodes website
     */
    protected $link;

    /**
     * @ORM\Column(name="description", type="text")
     *
     * @Assert\NotBlank
     *
     * @var string A description of the episode
     */
    protected $description;

    /**
     * @ORM\Column(name="publish_at", type="integer", nullable=true)
     *
     * @var int|null Timestamp of when the episode should be published
     */
    protected $publishAt;

    /**
     * @ORM\Column(name="explicit", type="boolean")
     *
     * @var bool Whether the episode contains explicit content or not
     */
    protected $explicit;

    /**
     * @ORM\Column(name="created_at", type="integer")
     *
     * @var int Timestamp of when the episode was created
     */
    protected $createdAt;

    public function __construct(Station $station, StationPodcast $podcast)
    {
        $this->station = $station;
        $this->podcast = $podcast;

        $this->createdAt = time();

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

    public function getPodcast(): StationPodcast
    {
        return $this->podcast;
    }

    public function getPodcastMedia(): ?StationPodcastMedia
    {
        return $this->podcastMedia;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $this->truncateString($title);

        return $this;
    }

    public function getLink(): string
    {
        return $this->link;
    }

    public function setLink(string $link): self
    {
        $this->link = $this->truncateString($link);

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $this->truncateString($description);

        return $this;
    }

    public function getPublishAt(): ?int
    {
        return $this->publishAt;
    }

    public function setPublishAt(?int $publishAt): self
    {
        $this->publishAt = $publishAt;

        return $this;
    }

    public function getExplicit(): bool
    {
        return $this->explicit;
    }

    public function setExplicit(bool $explicit): self
    {
        $this->explicit = $explicit;

        return $this;
    }

    public function getCreatedAt(): int
    {
        return $this->createdAt;
    }

    public function setCreatedAt(int $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public static function getArtworkPath(string $uniqueId): string
    {
        return self::DIR_PODCAST_EPISODE_ARTWORK . '/' . $uniqueId . '.jpg';
    }

    public function isPublished(): bool
    {
        if ($this->getPublishAt() !== null && $this->getPublishAt() > time()) {
            return false;
        }

        if ($this->getPodcastMedia() === null) {
            return false;
        }

        return true;
    }
}
