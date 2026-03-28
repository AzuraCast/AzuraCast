<?php

declare(strict_types=1);

namespace App\Entity;

use App\OpenApi;
use App\Validator\Constraints\UniqueEntity;
use Azura\Normalizer\Attributes\DeepNormalize;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use OpenApi\Attributes as OA;
use ReflectionException;
use ReflectionProperty;
use SensitiveParameter;
use Stringable;
use Symfony\Component\Serializer\Attribute as Serializer;
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
final class StationStreamer implements
    Stringable,
    Interfaces\StationAwareInterface,
    Interfaces\StationCloneAwareInterface,
    Interfaces\IdentifiableEntityInterface
{
    use Traits\HasAutoIncrementId;
    use Traits\TruncateStrings;

    #[
        ORM\ManyToOne(inversedBy: 'streamers'),
        ORM\JoinColumn(name: 'station_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')
    ]
    public Station $station;

    public function setStation(Station $station): void
    {
        $this->station = $station;
    }

    /* TODO Remove direct identifier access. */
    #[ORM\Column(nullable: false, insertable: false, updatable: false)]
    public private(set) int $station_id;

    #[
        OA\Property(example: "dj_test"),
        ORM\Column(length: 50),
        Assert\NotBlank
    ]
    public string $streamer_username {
        set => $this->truncateString($value, 50);
    }

    #[
        OA\Property(example: ""),
        ORM\Column(length: 255),
        Attributes\AuditIgnore
    ]
    public string $streamer_password {
        // @phpstan-ignore propertyGetHook.noRead
        get => '';
        // @phpstan-ignore propertySetHook.noAssign
        set (string|null $value) {
            $streamerPassword = trim($value ?? '');

            if (!empty($streamerPassword)) {
                $this->streamer_password = password_hash($streamerPassword, PASSWORD_ARGON2ID);
            }
        }
    }

    public function authenticate(
        #[SensitiveParameter]
        string $password
    ): bool {
        try {
            $reflProp = new ReflectionProperty($this, 'streamer_password');
            $hash = $reflProp->getRawValue($this);

            return password_verify($password, $hash);
        } catch (ReflectionException) {
            return false;
        }
    }

    #[
        OA\Property(example: "Test DJ"),
        ORM\Column(length: 255, nullable: false)
    ]
    public string $display_name = '' {
        get => !empty($this->display_name) ? $this->display_name : $this->streamer_username;
        set (string|null $value) => $this->truncateNullableString($value) ?? '';
    }

    #[
        OA\Property(example: "This is a test DJ account."),
        ORM\Column(type: 'text', nullable: true)
    ]
    public ?string $comments = null;

    #[
        OA\Property(example: true),
        ORM\Column
    ]
    public bool $is_active = true {
        set {
            $this->is_active = $value;

            // Automatically set the "reactivate_at" flag to null if the DJ is for any reason reactivated.
            if (true === $value) {
                $this->reactivate_at = null;
            }
        }
    }

    #[
        OA\Property(example: false),
        ORM\Column
    ]
    public bool $enforce_schedule = false;

    #[
        OA\Property(example: OpenApi::SAMPLE_TIMESTAMP),
        ORM\Column(nullable: true),
        Attributes\AuditIgnore
    ]
    public ?int $reactivate_at = null;

    #[
        ORM\Column,
        Attributes\AuditIgnore
    ]
    public int $art_updated_at = 0;

    /** @var Collection<int, StationSchedule> */
    #[
        OA\Property(type: "array", items: new OA\Items()),
        ORM\OneToMany(targetEntity: StationSchedule::class, mappedBy: 'streamer'),
        DeepNormalize(true),
        Serializer\MaxDepth(1)
    ]
    public private(set) Collection $schedule_items;

    /** @var Collection<int, StationStreamerBroadcast> */
    #[
        ORM\OneToMany(targetEntity: StationStreamerBroadcast::class, mappedBy: 'streamer'),
        DeepNormalize(true)
    ]
    public private(set) Collection $broadcasts;

    public function __construct(Station $station)
    {
        $this->station = $station;

        $this->schedule_items = new ArrayCollection();
        $this->broadcasts = new ArrayCollection();
    }

    public function deactivateFor(int $seconds): void
    {
        $this->is_active = false;
        $this->reactivate_at = time() + $seconds;
    }

    public function __toString(): string
    {
        return $this->station . ' Streamer: ' . $this->display_name;
    }

    public function __clone()
    {
        $this->reactivate_at = null;

        $this->schedule_items = new ArrayCollection();
        $this->broadcasts = new ArrayCollection();
    }

    public static function getArtworkPath(int|string $streamerId): string
    {
        return 'streamer_' . $streamerId . '.jpg';
    }
}
