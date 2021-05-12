<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Traits;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use JsonSerializable;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="station_podcast")
 * @ORM\Entity
 */
class StationPodcast implements JsonSerializable
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
     * @ORM\OneToMany(targetEntity="StationPodcastCategory", mappedBy="podcast")
     *
     * @var Collection
     */
    protected $categories;

    /**
     * @ORM\OneToMany(targetEntity="StationPodcastEpisode", mappedBy="podcast")
     *
     * @var Collection
     */
    protected $episodes;

    public function __construct(Station $station)
    {
        $this->station = $station;

        $this->categories = new ArrayCollection();
        $this->episodes = new ArrayCollection();

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

    public function getCategories(): Collection
    {
        return $this->categories;
    }

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
            'station_id' => $this->stationId,
            'title' => $this->title,
            'link' => $this->link,
            'description' => $this->description,
            'language' => $this->language,
            'categories' => [],
            'episodes' => [],
        ];

        /** @var StationPodcastCategory $category */
        foreach ($this->categories as $category) {
            $return['categories'][] = $category->getCategory()->getId();
        }

        /** @var StationPodcastEpisode $episode */
        foreach ($this->episodes as $episode) {
            $return['episodes'][] = $episode->getId();
        }

        return $return;
    }
}
