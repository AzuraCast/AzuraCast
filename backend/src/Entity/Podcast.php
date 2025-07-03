<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Enums\PodcastSources;
use Azura\Normalizer\Attributes\DeepNormalize;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[
    ORM\Entity,
    ORM\Table(name: 'podcast'),
    Attributes\Auditable
]
class Podcast implements Interfaces\IdentifiableEntityInterface
{
    use Traits\HasUniqueId;
    use Traits\TruncateStrings;

    public const string DIR_PODCAST_ARTWORK = '.podcast_art';

    #[ORM\ManyToOne(targetEntity: StorageLocation::class)]
    #[ORM\JoinColumn(name: 'storage_location_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected StorageLocation $storage_location;

    #[ORM\Column(nullable: false, insertable: false, updatable: false)]
    protected int $storage_location_id;

    #[DeepNormalize(true)]
    #[ORM\ManyToOne(inversedBy: 'podcasts')]
    #[ORM\JoinColumn(name: 'playlist_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    protected ?StationPlaylist $playlist = null;

    #[ORM\Column(nullable: true, insertable: false, updatable: false)]
    protected ?int $playlist_id = null;

    #[ORM\Column(type: 'string', length: 50, enumType: PodcastSources::class)]
    protected PodcastSources $source = PodcastSources::Manual;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    protected string $title;

    #[ORM\Column(length: 255, nullable: true)]
    protected ?string $link = null;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank]
    protected string $description;

    #[ORM\Column]
    protected bool $is_enabled = true;

    #[ORM\Column(type: 'json', nullable: true)]
    protected ?array $branding_config = null;

    #[ORM\Column(length: 2)]
    #[Assert\NotBlank]
    protected string $language;

    #[ORM\Column(length: 255)]
    protected string $author;

    #[ORM\Column(length: 255)]
    #[Assert\Email]
    protected string $email;

    #[ORM\Column]
    #[Attributes\AuditIgnore]
    protected int $art_updated_at = 0;

    #[ORM\Column]
    protected bool $playlist_auto_publish = true;

    /** @var Collection<int, PodcastCategory> */
    #[ORM\OneToMany(targetEntity: PodcastCategory::class, mappedBy: 'podcast')]
    protected Collection $categories;

    /** @var Collection<int, PodcastEpisode> */
    #[
        ORM\OneToMany(targetEntity: PodcastEpisode::class, mappedBy: 'podcast', fetch: 'EXTRA_LAZY'),
        ORM\OrderBy(['publish_at' => 'DESC'])
    ]
    protected Collection $episodes;

    public function __construct(StorageLocation $storageLocation)
    {
        $this->storage_location = $storageLocation;

        $this->categories = new ArrayCollection();
        $this->episodes = new ArrayCollection();
    }

    public function getStorageLocation(): StorageLocation
    {
        return $this->storage_location;
    }

    public function getPlaylist(): ?StationPlaylist
    {
        return $this->playlist;
    }

    public function setPlaylist(?StationPlaylist $playlist): void
    {
        $this->playlist = $playlist;
    }

    public function getSource(): PodcastSources
    {
        return $this->source;
    }

    public function setSource(PodcastSources $source): void
    {
        $this->source = $source;
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

    public function isEnabled(): bool
    {
        return $this->is_enabled;
    }

    public function setIsEnabled(bool $is_enabled): void
    {
        $this->is_enabled = $is_enabled;
    }

    public function getBrandingConfig(): PodcastBrandingConfiguration
    {
        return new PodcastBrandingConfiguration((array)$this->branding_config);
    }

    public function setBrandingConfig(
        PodcastBrandingConfiguration|array $brandingConfig
    ): void {
        $this->branding_config = $this->getBrandingConfig()
            ->fromArray($brandingConfig)
            ->toArray();
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function setLanguage(string $language): self
    {
        $this->language = $this->truncateString($language);

        return $this;
    }

    public function getAuthor(): string
    {
        return $this->author;
    }

    public function setAuthor(string $author): self
    {
        $this->author = $this->truncateString($author);

        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $this->truncateString($email);

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

    public function playlistAutoPublish(): bool
    {
        return $this->playlist_auto_publish;
    }

    public function setPlaylistAutoPublish(bool $playlist_auto_publish): void
    {
        $this->playlist_auto_publish = $playlist_auto_publish;
    }

    /**
     * @return Collection<int, PodcastCategory>
     */
    public function getCategories(): Collection
    {
        return $this->categories;
    }

    /**
     * @return Collection<int, PodcastEpisode>
     */
    public function getEpisodes(): Collection
    {
        return $this->episodes;
    }

    public static function getArtPath(string $uniqueId): string
    {
        return self::DIR_PODCAST_ARTWORK . '/' . $uniqueId . '.jpg';
    }
}
