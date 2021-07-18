<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Interfaces\IdentifiableEntityInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use OpenApi\Annotations as OA;

/** @OA\Schema(type="object") */
#[
    ORM\Entity,
    ORM\Table(name: 'relays'),
    ORM\HasLifecycleCallbacks
]
class Relay implements IdentifiableEntityInterface
{
    use Traits\HasAutoIncrementId;
    use Traits\TruncateStrings;

    /** @OA\Property(example="https://custom-url.example.com") */
    #[ORM\Column(length: 255)]
    protected string $base_url;

    /** @OA\Property(example="Relay") */
    #[ORM\Column(length: 100, nullable: true)]
    protected ?string $name = 'Relay';

    /** @OA\Property(example=true) */
    #[ORM\Column]
    protected bool $is_visible_on_public_pages = true;

    #[ORM\Column(type: 'array', nullable: true)]
    protected mixed $nowplaying;

    /** @OA\Property(example=SAMPLE_TIMESTAMP) */
    #[ORM\Column]
    protected int $created_at;

    /** @OA\Property(example=SAMPLE_TIMESTAMP) */
    #[ORM\Column]
    protected int $updated_at;

    #[ORM\OneToMany(mappedBy: 'relay', targetEntity: StationRemote::class)]
    protected Collection $remotes;

    public function __construct(string $base_url)
    {
        $this->base_url = $this->truncateString($base_url);

        $this->created_at = time();
        $this->updated_at = time();

        $this->remotes = new ArrayCollection();
    }

    #[ORM\PreUpdate]
    public function preUpdate(): void
    {
        $this->updated_at = time();
    }

    public function getBaseUrl(): string
    {
        return $this->base_url;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $this->truncateNullableString($name, 100);
    }

    public function isIsVisibleOnPublicPages(): bool
    {
        return $this->is_visible_on_public_pages;
    }

    public function setIsVisibleOnPublicPages(bool $is_visible_on_public_pages): void
    {
        $this->is_visible_on_public_pages = $is_visible_on_public_pages;
    }

    public function getNowplaying(): mixed
    {
        return $this->nowplaying;
    }

    public function setNowplaying(mixed $nowplaying): void
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
