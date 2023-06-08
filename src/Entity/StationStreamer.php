<?php

declare(strict_types=1);

namespace App\Entity;

use App\Normalizer\Attributes\DeepNormalize;
use App\OpenApi;
use App\Validator\Constraints\UniqueEntity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use OpenApi\Attributes as OA;
use Stringable;
use Symfony\Component\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

use const PASSWORD_ARGON2ID;

#[
    OA\Schema(
        description: 'Station streamers (DJ accounts) allowed to broadcast to a station.',
        type: "object"
    ),
    ORM\Entity,
    ORM\Table(name: 'station_streamers'),
    ORM\UniqueConstraint(name: 'username_unique_idx', columns: ['station_id', 'streamer_username']),
    ORM\HasLifecycleCallbacks,
    UniqueEntity(fields: ['station', 'streamer_username']),
    Attributes\Auditable
]
class StationStreamer implements
    Stringable,
    Interfaces\StationCloneAwareInterface,
    Interfaces\IdentifiableEntityInterface
{
    use Traits\HasAutoIncrementId;
    use Traits\TruncateStrings;

    #[
        ORM\ManyToOne(inversedBy: 'streamers'),
        ORM\JoinColumn(name: 'station_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')
    ]
    protected Station $station;

    #[ORM\Column(nullable: false, insertable: false, updatable: false)]
    protected int $station_id;

    #[
        OA\Property(example: "dj_test"),
        ORM\Column(length: 50),
        Assert\NotBlank
    ]
    protected string $streamer_username;

    #[
        OA\Property(example: ""),
        ORM\Column(length: 255),
        Assert\NotBlank,
        Attributes\AuditIgnore
    ]
    protected string $streamer_password;

    #[
        OA\Property(example: "Test DJ"),
        ORM\Column(length: 255, nullable: true)
    ]
    protected ?string $display_name = null;

    #[
        OA\Property(example: "This is a test DJ account."),
        ORM\Column(type: 'text', nullable: true)
    ]
    protected ?string $comments = null;

    #[
        OA\Property(example: true),
        ORM\Column
    ]
    protected bool $is_active = true;

    #[
        OA\Property(example: false),
        ORM\Column
    ]
    protected bool $enforce_schedule = false;

    #[
        OA\Property(example: OpenApi::SAMPLE_TIMESTAMP),
        ORM\Column(nullable: true),
        Attributes\AuditIgnore
    ]
    protected ?int $reactivate_at = null;

    #[
        ORM\Column,
        Attributes\AuditIgnore
    ]
    protected int $art_updated_at = 0;

    /** @var Collection<int, StationSchedule> */
    #[
        OA\Property(type: "array", items: new OA\Items()),
        ORM\OneToMany(mappedBy: 'streamer', targetEntity: StationSchedule::class),
        DeepNormalize(true),
        Serializer\MaxDepth(1)
    ]
    protected Collection $schedule_items;

    public function __construct(Station $station)
    {
        $this->station = $station;
        $this->schedule_items = new ArrayCollection();
    }

    public function getStation(): Station
    {
        return $this->station;
    }

    public function setStation(Station $station): void
    {
        $this->station = $station;
    }

    public function getStreamerUsername(): string
    {
        return $this->streamer_username;
    }

    public function setStreamerUsername(string $streamerUsername): void
    {
        $this->streamer_username = $this->truncateString($streamerUsername, 50);
    }

    public function getStreamerPassword(): string
    {
        return '';
    }

    public function setStreamerPassword(?string $streamerPassword): void
    {
        $streamerPassword = trim($streamerPassword ?? '');

        if (!empty($streamerPassword)) {
            $this->streamer_password = password_hash($streamerPassword, PASSWORD_ARGON2ID);
        }
    }

    public function authenticate(string $password): bool
    {
        return password_verify($password, $this->streamer_password);
    }

    public function getDisplayName(): string
    {
        return (!empty($this->display_name))
            ? $this->display_name
            : $this->streamer_username;
    }

    public function setDisplayName(?string $displayName): void
    {
        $this->display_name = $this->truncateNullableString($displayName);
    }

    public function getComments(): ?string
    {
        return $this->comments;
    }

    public function setComments(string $comments = null): void
    {
        $this->comments = $comments;
    }

    public function getIsActive(): bool
    {
        return $this->is_active;
    }

    public function setIsActive(bool $isActive): void
    {
        $this->is_active = $isActive;

        // Automatically set the "reactivate_at" flag to null if the DJ is for any reason reactivated.
        if (true === $isActive) {
            $this->reactivate_at = null;
        }
    }

    public function enforceSchedule(): bool
    {
        return $this->enforce_schedule;
    }

    public function setEnforceSchedule(bool $enforceSchedule): void
    {
        $this->enforce_schedule = $enforceSchedule;
    }

    public function getReactivateAt(): ?int
    {
        return $this->reactivate_at;
    }

    public function setReactivateAt(?int $reactivateAt): void
    {
        $this->reactivate_at = $reactivateAt;
    }

    public function deactivateFor(int $seconds): void
    {
        $this->is_active = false;
        $this->reactivate_at = time() + $seconds;
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
     * @return Collection<int, StationSchedule>
     */
    public function getScheduleItems(): Collection
    {
        return $this->schedule_items;
    }

    public function __toString(): string
    {
        return $this->getStation() . ' Streamer: ' . $this->getDisplayName();
    }

    public function __clone()
    {
        $this->reactivate_at = null;
    }

    public static function getArtworkPath(int|string $streamerId): string
    {
        return 'streamer_' . $streamerId . '.jpg';
    }
}
