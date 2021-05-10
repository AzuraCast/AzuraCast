<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="station_podcast_category")
 * @ORM\Entity
 */
class StationPodcastCategory
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     *
     * @var int|null
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="StationPodcast", inversedBy="categories")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="podcast_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     *
     * @var StationPodcast
     */
    protected $podcast;

    /**
     * @ORM\ManyToOne(targetEntity="PodcastCategory", inversedBy="podcasts")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="category_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     *
     * @var PodcastCategory
     */
    protected $category;

    public function __construct(StationPodcast $podcast, PodcastCategory $category)
    {
        $this->podcast = $podcast;
        $this->category = $category;
    }

    public function getPodcast(): StationPodcast
    {
        return $this->podcast;
    }

    public function getCategory(): PodcastCategory
    {
        return $this->category;
    }
}
