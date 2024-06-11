<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Enums\PodcastSources;
use App\Entity\Interfaces\IdentifiableEntityInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[
    ORM\Entity,
    ORM\Table(name: 'podcast_episode'),
    Attributes\Auditable
]
class PodcastEpisode implements IdentifiableEntityInterface
{
    use Traits\HasUniqueId;
    use Traits\TruncateStrings;

    public const string DIR_PODCAST_EPISODE_ARTWORK = '.podcast_episode_art';

    #[ORM\ManyToOne(inversedBy: 'episodes')]
    #[ORM\JoinColumn(name: 'podcast_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected Podcast $podcast;

    #[ORM\ManyToOne(inversedBy: 'podcast_episodes')]
    #[ORM\JoinColumn(name: 'playlist_media_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    protected ?StationMedia $playlist_media = null;

    #[ORM\Column(nullable: true, insertable: false, updatable: false)]
    protected ?int $playlist_media_id = null;

    #[ORM\OneToOne(mappedBy: 'episode')]
    protected ?PodcastMedia $media = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    protected string $title;

    #[ORM\Column(length: 255, nullable: true)]
    protected ?string $link = null;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank]
    protected string $description;

    #[ORM\Column]
    protected int $publish_at;

    #[ORM\Column]
    protected bool $explicit;

    #[ORM\Column(nullable: true)]
    protected ?int $season_number;

    #[ORM\Column(nullable: true)]
    protected ?int $episode_number;

    #[ORM\Column]
    protected int $created_at;

    #[ORM\Column]
    #[Attributes\AuditIgnore]
    protected int $art_updated_at = 0;

    public function __construct(Podcast $podcast)
    {
        $this->podcast = $podcast;
        $this->created_at = time();
        $this->publish_at = time();
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

    public function getPlaylistMedia(): ?StationMedia
    {
        return $this->playlist_media;
    }

    public function setPlaylistMedia(?StationMedia $playlist_media): void
    {
        $this->playlist_media = $playlist_media;
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

    public function getLink(): ?string
    {
        return $this->link;
    }

    public function setLink(?string $link): self
    {
        $this->link = $this->truncateNullableString($link);

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $this->truncateString($description, 4000);

        return $this;
    }

    public function getPublishAt(): int
    {
        return $this->publish_at;
    }

    public function setPublishAt(?int $publishAt): self
    {
        $this->publish_at = $publishAt ?? $this->created_at;

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

    public function getSeasonNumber(): ?int
    {
        return $this->season_number;
    }

    public function setSeasonNumber(?int $season_number): self
    {
        $this->season_number = $season_number;

        return $this;
    }

    public function getEpisodeNumber(): ?int
    {
        return $this->episode_number;
    }

    public function setEpisodeNumber(?int $episode_number): self
    {
        $this->episode_number = $episode_number;

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

    public function setArtUpdatedAt(int $artUpdatedAt): self
    {
        $this->art_updated_at = $artUpdatedAt;

        return $this;
    }

    public static function getArtPath(string $uniqueId): string
    {
        return self::DIR_PODCAST_EPISODE_ARTWORK . '/' . $uniqueId . '.jpg';
    }

    public function isPublished(): bool
    {
        if ($this->getPublishAt() > time()) {
            return false;
        }

        return match ($this->getPodcast()->getSource()) {
            PodcastSources::Manual => ($this->getMedia() !== null),
            PodcastSources::Playlist => ($this->getPlaylistMedia() !== null)
        };
    }
}
