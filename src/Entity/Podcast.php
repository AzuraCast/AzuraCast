<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Traits;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="podcast")
 * @ORM\Entity
 */
class Podcast implements JsonSerializable
{
    use Traits\UniqueId;
    use Traits\TruncateStrings;

    public const UNIQUE_ID_LENGTH = 24;

    public const DIR_PODCAST_ARTWORK = '.podcast_art';

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     *
     * @var int|null
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
     * @ORM\Column(name="title", type="string", length=255)
     *
     * @Assert\NotBlank
     *
     * @var string The name of your podcast
     */
    protected $title;

    /**
     * @ORM\Column(name="link", type="string", length=255, nullable=true)
     *
     * @var string|null A link to your website
     */
    protected $link;

    /**
     * @ORM\Column(name="description", type="text")
     *
     * @Assert\NotBlank
     *
     * @var string A description of your podcast
     */
    protected $description;

    /**
     * @ORM\Column(name="language", type="string", length=2)
     *
     * @Assert\NotBlank
     *
     * @var string The ISO 639-1 language code for your podcast
     */
    protected $language;

    /**
     * @ORM\OneToMany(targetEntity="PodcastCategory", mappedBy="podcast")
     *
     * @var Collection
     */
    protected $categories;

    /**
     * @ORM\OneToMany(targetEntity="PodcastEpisode", mappedBy="podcast")
     *
     * @var Collection
     */
    protected $episodes;

    public function __construct(StorageLocation $storageLocation)
    {
        $this->storage_location = $storageLocation;

        $this->categories = new ArrayCollection();
        $this->episodes = new ArrayCollection();

        $this->generateUniqueId();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function setLanguage(string $language): self
    {
        $this->language = $this->truncateString($language);

        return $this;
    }

    /**
     * @return Collection|PodcastCategory[]
     */
    public function getCategories(): Collection
    {
        return $this->categories;
    }

    /**
     * @return Collection|PodcastEpisode[]
     */
    public function getEpisodes(): Collection
    {
        return $this->episodes;
    }

    public static function getArtworkPath(string $uniqueId): string
    {
        return self::DIR_PODCAST_ARTWORK . '/' . $uniqueId . '.jpg';
    }

    /**
     * @return mixed[]
     */
    public function jsonSerialize(): array
    {
        $return = [
            'id' => $this->id,
            'unique_id' => $this->unique_id,
            'storage_location_id' => $this->storage_location->getId(),
            'title' => $this->title,
            'link' => $this->link,
            'description' => $this->description,
            'language' => $this->language,
            'categories' => [],
            'episodes' => [],
        ];

        /** @var PodcastCategory $category */
        foreach ($this->categories as $category) {
            $return['categories'][] = $category->getCategory();
        }

        /** @var PodcastEpisode $episode */
        foreach ($this->episodes as $episode) {
            $return['episodes'][] = $episode->getId();
        }

        return $return;
    }
}
