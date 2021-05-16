<?php

declare(strict_types=1);

namespace App\Entity;

use App\Annotations\AuditLog;
use App\Entity\Traits;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="podcast_episode")
 * @ORM\Entity
 *
 * @AuditLog\Auditable
 */
class PodcastEpisode implements \JsonSerializable
{
    use Traits\TruncateStrings;

    public const DIR_PODCAST_EPISODE_ARTWORK = '.podcast_episode_art';

    /**
     * @ORM\Id
     * @ORM\Column(name="id", type="guid", unique=true)
     * @ORM\GeneratedValue(strategy="UUID")
     *
     * @var string|null
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="Podcast", inversedBy="episodes")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="podcast_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     *
     * @var Podcast
     */
    protected $podcast;

    /**
     * @ORM\OneToOne(targetEntity="PodcastMedia", mappedBy="episode")
     *
     * @var PodcastMedia|null
     */
    protected $media;

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
    protected $publish_at;

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
    protected $created_at;

    /**
     * @ORM\Column(name="art_updated_at", type="integer")
     * @AuditLog\AuditIgnore()
     *
     * @var int The latest time (UNIX timestamp) when album art was updated.
     */
    protected $art_updated_at = 0;

    public function __construct(Podcast $podcast)
    {
        $this->podcast = $podcast;
        $this->created_at = time();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getPodcast(): Podcast
    {
        return $this->podcast;
    }

    public function setMedia(?PodcastMedia $media): void
    {
        $this->media = $media;
    }

    public function getMedia(): ?PodcastMedia
    {
        return $this->media;
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
        return $this->publish_at;
    }

    public function setPublishAt(?int $publishAt): self
    {
        $this->publish_at = $publishAt;

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
        return $this->created_at;
    }

    public function setCreatedAt(int $createdAt): self
    {
        $this->created_at = $createdAt;

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
        return self::DIR_PODCAST_EPISODE_ARTWORK . '/' . $uniqueId . '.jpg';
    }

    public function isPublished(): bool
    {
        if ($this->getPublishAt() !== null && $this->getPublishAt() > time()) {
            return false;
        }

        if ($this->getMedia() === null) {
            return false;
        }

        return true;
    }

    /**
     * @return mixed[]
     */
    public function jsonSerialize(): array
    {
        $return = [
            'id' => $this->getId(),
            'title' => $this->getTitle(),
            'description' => $this->getDescription(),
            'explicit' => $this->getExplicit(),
            'publish_at' => $this->getPublishAt(),
            'has_media' => ($this->getMedia() !== null),
            'podcast_media' => null,
            'has_custom_art' => (0 !== $this->getArtUpdatedAt()),
        ];

        $episodePodcastMedia = $this->getMedia();

        if ($episodePodcastMedia !== null) {
            $return['podcast_media'] = [
                'id' => $episodePodcastMedia->getId(),
                'original_name' => $episodePodcastMedia->getOriginalName(),
                'length' => $episodePodcastMedia->getLength(),
                'length_text' => $episodePodcastMedia->getLengthText(),
                'path' => $episodePodcastMedia->getPath(),
            ];
        }

        return $return;
    }
}
