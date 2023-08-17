<?php

declare(strict_types=1);

namespace App\Entity;

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

    public const DIR_PODCAST_ARTWORK = '.podcast_art';

    #[ORM\ManyToOne(targetEntity: StorageLocation::class)]
    #[ORM\JoinColumn(name: 'storage_location_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected StorageLocation $storage_location;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    protected string $title;

    #[ORM\Column(length: 255, nullable: true)]
    protected ?string $link = null;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank]
    protected string $description;

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

    /** @var Collection<int, PodcastCategory> */
    #[ORM\OneToMany(mappedBy: 'podcast', targetEntity: PodcastCategory::class)]
    protected Collection $categories;

    /** @var Collection<int, PodcastEpisode> */
    #[ORM\OneToMany(mappedBy: 'podcast', targetEntity: PodcastEpisode::class)]
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
