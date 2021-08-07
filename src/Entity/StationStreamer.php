<?php

declare(strict_types=1);

namespace App\Entity;

use App\Normalizer\Attributes\DeepNormalize;
use App\Validator\Constraints\UniqueEntity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use OpenApi\Annotations as OA;
use Stringable;
use Symfony\Component\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

use const PASSWORD_ARGON2ID;

/**
 * Station streamers (DJ accounts) allowed to broadcast to a station.
 *
 * @OA\Schema(type="object")
 */
#[
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

    #[ORM\Column(nullable: false)]
    protected int $station_id;

    #[ORM\ManyToOne(inversedBy: 'streamers')]
    #[ORM\JoinColumn(name: 'station_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected Station $station;

    /** @OA\Property(example="dj_test") */
    #[ORM\Column(length: 50)]
    #[Assert\NotBlank]
    protected string $streamer_username;

    /** @OA\Property(example="") */
    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Attributes\AuditIgnore]
    protected string $streamer_password;

    /** @OA\Property(example="Test DJ") */
    #[ORM\Column(length: 255, nullable: true)]
    protected ?string $display_name = null;

    /** @OA\Property(example="This is a test DJ account.") */
    #[ORM\Column(type: 'text', nullable: true)]
    protected ?string $comments = null;

    /** @OA\Property(example=true) */
    #[ORM\Column]
    protected bool $is_active = true;

    /** @OA\Property(example=false) */
    #[ORM\Column]
    protected bool $enforce_schedule = false;

    /** @OA\Property(example=SAMPLE_TIMESTAMP) */
    #[ORM\Column(nullable: true)]
    #[Attributes\AuditIgnore]
    protected ?int $reactivate_at = null;

    /**
     * @OA\Property(
     *     type="array",
     *     @OA\Items()
     * )
     */
    #[ORM\OneToMany(mappedBy: 'streamer', targetEntity: StationSchedule::class)]
    #[DeepNormalize(true)]
    #[Serializer\MaxDepth(1)]
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

    public function setStreamerUsername(string $streamer_username): void
    {
        $this->streamer_username = $this->truncateString($streamer_username, 50);
    }

    public function getStreamerPassword(): string
    {
        return '';
    }

    public function setStreamerPassword(?string $streamer_password): void
    {
        $streamer_password = trim($streamer_password ?? '');

        if (!empty($streamer_password)) {
            $this->streamer_password = password_hash($streamer_password, PASSWORD_ARGON2ID);
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

    public function setDisplayName(?string $display_name): void
    {
        $this->display_name = $this->truncateNullableString($display_name);
    }

    public function getComments(): ?string
    {
        return $this->comments;
    }

    public function setComments(string $comments = null): void
    {
        $this->comments = $comments;
    }

    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function setIsActive(bool $is_active): void
    {
        $this->is_active = $is_active;

        // Automatically set the "reactivate_at" flag to null if the DJ is for any reason reactivated.
        if (true === $is_active) {
            $this->reactivate_at = null;
        }
    }

    public function enforceSchedule(): bool
    {
        return $this->enforce_schedule;
    }

    public function setEnforceSchedule(bool $enforce_schedule): void
    {
        $this->enforce_schedule = $enforce_schedule;
    }

    public function getReactivateAt(): ?int
    {
        return $this->reactivate_at;
    }

    public function setReactivateAt(?int $reactivate_at): void
    {
        $this->reactivate_at = $reactivate_at;
    }

    public function deactivateFor(int $seconds): void
    {
        $this->is_active = false;
        $this->reactivate_at = time() + $seconds;
    }

    /**
     * @return Collection|StationSchedule[]
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
}
