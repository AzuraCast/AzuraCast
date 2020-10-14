<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use OpenApi\Annotations as OA;

/**
 * AzuraRelay instances
 *
 * @ORM\Table(name="relays")
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks()
 *
 * @OA\Schema(type="object")
 */
class Relay
{
    use Traits\TruncateStrings;

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     *
     * @OA\Property(example=1)
     * @var int
     */
    protected $id;

    /**
     * @ORM\Column(name="base_url", type="string", length=255)
     *
     * @OA\Property(example="http://custom-url.example.com")
     *
     * @var string
     */
    protected $base_url;

    /**
     * @ORM\Column(name="name", type="string", length=100, nullable=true)
     *
     * @OA\Property(example="Relay")
     * @var string|null
     */
    protected $name = 'Relay';

    /**
     * @ORM\Column(name="is_visible_on_public_pages", type="boolean")
     *
     * @OA\Property(example=true)
     * @var bool
     */
    protected $is_visible_on_public_pages = true;

    /**
     * @ORM\Column(name="nowplaying", type="array", nullable=true)
     * @var mixed|null
     */
    protected $nowplaying;

    /**
     * @ORM\Column(name="created_at", type="integer")
     *
     * @OA\Property(example=SAMPLE_TIMESTAMP)
     * @var int
     */
    protected $created_at;

    /**
     * @ORM\Column(name="updated_at", type="integer")
     *
     * @OA\Property(example=SAMPLE_TIMESTAMP)
     * @var int
     */
    protected $updated_at;

    /**
     * @ORM\OneToMany(targetEntity="StationRemote", mappedBy="relay")
     * @var Collection
     */
    protected $remotes;

    public function __construct(string $base_url)
    {
        $this->base_url = $this->truncateString($base_url);

        $this->created_at = time();
        $this->updated_at = time();

        $this->remotes = new ArrayCollection();
    }

    /**
     * @ORM\PreUpdate
     */
    public function preUpdate(): void
    {
        $this->updated_at = time();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getBaseUrl(): ?string
    {
        return $this->base_url;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $this->truncateString($name, 100);
    }

    public function isIsVisibleOnPublicPages(): bool
    {
        return $this->is_visible_on_public_pages;
    }

    public function setIsVisibleOnPublicPages(bool $is_visible_on_public_pages): void
    {
        $this->is_visible_on_public_pages = $is_visible_on_public_pages;
    }

    /**
     * @return mixed|null
     */
    public function getNowplaying()
    {
        return $this->nowplaying;
    }

    public function setNowplaying($nowplaying): void
    {
        $this->nowplaying = $nowplaying;
    }

    public function getCreatedAt(): int
    {
        return $this->created_at;
    }

    public function setCreatedAt(int $created_at): void
    {
        $this->created_at = $created_at;
    }

    public function getUpdatedAt(): int
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(int $updated_at): void
    {
        $this->updated_at = $updated_at;
    }

    public function getRemotes(): Collection
    {
        return $this->remotes;
    }
}
