<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Traits;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="podcast_category")
 * @ORM\Entity
 */
class PodcastCategory
{
    use Traits\TruncateStrings;

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     *
     * @var int|null
     */
    protected $id;

    /**
     * @ORM\Column(name="title", type="string", length=255)
     *
     * @Assert\NotBlank
     *
     * @var string The name of the category
     */
    protected $title;

    /**
     * @ORM\Column(name="sub_title", type="string", length=255, nullable=true)
     *
     * @var string|null The name of the sub-category
     */
    protected $sub_title;

    /**
     * @ORM\OneToMany(targetEntity="StationPodcastCategory", mappedBy="category")
     *
     * @var ArrayCollection
     */
    protected $podcasts;

    public function __construct()
    {
        $this->podcasts = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getSubTitle(): ?string
    {
        return $this->sub_title;
    }

    public function setSubTitle(?string $subTitle): self
    {
        $this->sub_title = $subTitle;

        return $this;
    }

    public function getPodcasts(): ArrayCollection
    {
        return $this->podcasts;
    }

    public function setPodcasts(ArrayCollection $podcasts): self
    {
        $this->podcasts = $podcasts;

        return $this;
    }
}
