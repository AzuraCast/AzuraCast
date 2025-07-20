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
final class Relay implements IdentifiableEntityInterface
{
    use Traits\HasAutoIncrementId;
    use Traits\TruncateStrings;

    #[
        OA\Property(example: "https://custom-url.example.com"),
        ORM\Column(length: 255)
    ]
    public readonly string $base_url;

    #[
        OA\Property(example: "Relay"),
        ORM\Column(length: 100, nullable: true)
    ]
    public ?string $name = 'Relay' {
        set => $this->truncateNullableString($value, 100);
    }

    #[
        OA\Property(example: true),
        ORM\Column
    ]
    public bool $is_visible_on_public_pages = true;

    #[
        OA\Property(
            type: 'string',
            format: 'date-time',
            example: OpenApi::SAMPLE_DATETIME
        ),
        ORM\Column(type: 'datetime_immutable', precision: 6)
    ]
    public readonly DateTimeImmutable $created_at;

    #[
        OA\Property(
            type: 'string',
            format: 'date-time',
            example: OpenApi::SAMPLE_DATETIME
        ),
        ORM\Column(type: 'datetime_immutable', precision: 6)
    ]
    public DateTimeImmutable $updated_at;

    /** @var Collection<int, StationRemote> */
    #[ORM\OneToMany(targetEntity: StationRemote::class, mappedBy: 'relay')]
    public private(set) Collection $remotes;

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

    public function __clone(): void
    {
        $this->remotes = new ArrayCollection();
    }
}
