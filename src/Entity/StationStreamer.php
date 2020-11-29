<?php

namespace App\Entity;

use App\Annotations\AuditLog;
use App\Normalizer\Annotation\DeepNormalize;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use OpenApi\Annotations as OA;
use Symfony\Component\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

use const PASSWORD_ARGON2ID;

/**
 * Station streamers (DJ accounts) allowed to broadcast to a station.
 *
 * @ORM\Table(name="station_streamers", uniqueConstraints={
 *   @ORM\UniqueConstraint(name="username_unique_idx", columns={"station_id", "streamer_username"})
 * })
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks
 *
 * @AuditLog\Auditable
 *
 * @OA\Schema(type="object")
 */
class StationStreamer
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
     * @ORM\Column(name="station_id", type="integer")
     * @var int
     */
    protected $station_id;

    /**
     * @ORM\ManyToOne(targetEntity="Station", inversedBy="streamers")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="station_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     * @var Station
     */
    protected $station;

    /**
     * @ORM\Column(name="streamer_username", type="string", length=50, nullable=false)
     *
     * @OA\Property(example="dj_test")
     * @Assert\NotBlank()
     * @var string
     */
    protected $streamer_username;

    /**
     * @ORM\Column(name="streamer_password", type="string", length=255, nullable=false)
     *
     * @AuditLog\AuditIgnore()
     *
     * @Assert\NotBlank()
     * @OA\Property(example="")
     * @var string
     */
    protected $streamer_password;

    /**
     * @ORM\Column(name="display_name", type="string", length=255, nullable=true)
     *
     * @OA\Property(example="Test DJ")
     * @var string|null
     */
    protected $display_name;

    /**
     * @ORM\Column(name="comments", type="text", nullable=true)
     *
     * @OA\Property(example="This is a test DJ account.")
     * @var string|null
     */
    protected $comments;

    /**
     * @ORM\Column(name="is_active", type="boolean", nullable=false)
     *
     * @OA\Property(example=true)
     * @var bool
     */
    protected $is_active = true;

    /**
     * @ORM\Column(name="enforce_schedule", type="boolean", nullable=false)
     *
     * @OA\Property(example=false)
     * @var bool
     */
    protected $enforce_schedule = false;

    /**
     * @ORM\Column(name="reactivate_at", type="integer", nullable=true)
     *
     * @AuditLog\AuditIgnore()
     *
     * @OA\Property(example=SAMPLE_TIMESTAMP)
     * @var int|null
     */
    protected $reactivate_at;

    /**
     * @ORM\OneToMany(targetEntity="StationSchedule", mappedBy="streamer")
     * @var Collection
     *
     * @DeepNormalize(true)
     * @Serializer\MaxDepth(1)
     * @OA\Property(
     *     @OA\Items()
     * )
     */
    protected $schedule_items;

    public function __construct(Station $station)
    {
        $this->station = $station;
        $this->schedule_items = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getStation(): Station
    {
        return $this->station;
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
        $streamer_password = trim($streamer_password);

        if (!empty($streamer_password)) {
            $this->streamer_password = password_hash($streamer_password, PASSWORD_ARGON2ID);
        }
    }

    public function authenticate(string $password): bool
    {
        return password_verify($password, $this->streamer_password);
    }

    /**
     * @AuditLog\AuditIdentifier()
     */
    public function getDisplayName(): string
    {
        return (!empty($this->display_name))
            ? $this->display_name
            : $this->streamer_username;
    }

    public function setDisplayName(?string $display_name): void
    {
        $this->display_name = $this->truncateString($display_name);
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
}
