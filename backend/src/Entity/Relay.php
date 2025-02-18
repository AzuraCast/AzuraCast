<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Interfaces\IdentifiableEntityInterface;
use App\OpenApi;
use App\Utilities\Time;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use OpenApi\Attributes as OA;

#[
    OA\Schema(type: "object"),
    ORM\Entity,
    ORM\Table(name: 'relays'),
    ORM\HasLifecycleCallbacks
]
class Relay implements IdentifiableEntityInterface
{
    use Traits\HasAutoIncrementId;
    use Traits\TruncateStrings;

    #[
        OA\Property(example: "https://custom-url.example.com"),
        ORM\Column(length: 255)
    ]
    protected string $base_url;

    #[
        OA\Property(example: "Relay"),
        ORM\Column(length: 100, nullable: true)
    ]
    protected ?string $name = 'Relay';

    #[
        OA\Property(example: true),
        ORM\Column
    ]
    protected bool $is_visible_on_public_pages = true;

    #[
        OA\Property(example: OpenApi::SAMPLE_DATETIME),
        ORM\Column(type: 'datetime_immutable', precision: 6)
    ]
    protected DateTimeImmutable $created_at;

    #[
        OA\Property(example: OpenApi::SAMPLE_DATETIME),
        ORM\Column(type: 'datetime_immutable', precision: 6)
    ]
    protected DateTimeImmutable $updated_at;

    /** @var Collection<int, StationRemote> */
    #[ORM\OneToMany(targetEntity: StationRemote::class, mappedBy: 'relay')]
    protected Collection $remotes;

    public function __construct(string $baseUrl)
    {
        $this->base_url = $this->truncateString($baseUrl);

        $now = Time::nowUtc();
        $this->created_at = $now;
        $this->updated_at = $now;

        $this->remotes = new ArrayCollection();
    }

    #[ORM\PreUpdate]
    public function preUpdate(): void
    {
        $this->updated_at = Time::nowUtc();
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

    public function getIsVisibleOnPublicPages(): bool
    {
        return $this->is_visible_on_public_pages;
    }

    public function setIsVisibleOnPublicPages(bool $isVisibleOnPublicPages): void
    {
        $this->is_visible_on_public_pages = $isVisibleOnPublicPages;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->created_at;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updated_at;
    }

    /**
     * @return Collection<int, StationRemote>
     */
    public function getRemotes(): Collection
    {
        return $this->remotes;
    }
}
