<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Enums\PlaylistOrders;
use App\Entity\Enums\PlaylistRemoteTypes;
use App\Entity\Enums\PlaylistSources;
use App\Entity\Enums\PlaylistTypes;
use App\Utilities\File;
use App\Utilities\Time;
use Azura\Normalizer\Attributes\DeepNormalize;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use OpenApi\Attributes as OA;
use Stringable;
use Symfony\Component\Serializer\Attribute as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

#[
    OA\Schema(type: "object"),
    ORM\Entity,
    ORM\Table(name: 'station_playlists'),
    ORM\HasLifecycleCallbacks,
    Attributes\Auditable
]
final class StationPlaylist implements
    Stringable,
    Interfaces\StationAwareInterface,
    Interfaces\StationCloneAwareInterface,
    Interfaces\IdentifiableEntityInterface
{
    use Traits\HasAutoIncrementId;
    use Traits\TruncateStrings;

    public const int DEFAULT_WEIGHT = 3;
    public const int DEFAULT_REMOTE_BUFFER = 20;

    public const string OPTION_INTERRUPT_OTHER_SONGS = 'interrupt';
    public const string OPTION_PLAY_SINGLE_TRACK = 'single_track';
    public const string OPTION_MERGE = 'merge';
    public const string OPTION_PRIORITIZE_OVER_REQUESTS = 'prioritize';

    #[
        ORM\ManyToOne(inversedBy: 'playlists'),
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
        OA\Property(example: "Test Playlist"),
        ORM\Column(length: 200),
        Assert\NotBlank
    ]
    public string $name {
        set => $this->truncateString(
            str_replace(';', ':', $value),
            200
        );
    }

    #[
        OA\Property(example: "A playlist containing my favorite songs"),
        ORM\Column(type: 'text', nullable: true)
    ]
    public ?string $description = null;

    #[
        OA\Property(example: "default"),
        ORM\Column(type: 'string', length: 50, enumType: PlaylistTypes::class)
    ]
    public PlaylistTypes $type;

    #[
        OA\Property(example: "songs"),
        ORM\Column(type: 'string', length: 50, enumType: PlaylistSources::class)
    ]
    public PlaylistSources $source {
        set {
            $this->source = $value;

            if (PlaylistSources::RemoteUrl === $value) {
                $this->type = PlaylistTypes::Standard;
            }
        }
    }

    #[
        OA\Property(example: "shuffle"),
        ORM\Column(name: 'playback_order', type: 'string', length: 50, enumType: PlaylistOrders::class)
    ]
    public PlaylistOrders $order;

    #[
        OA\Property(example: "https://remote-url.example.com/stream.mp3"),
        ORM\Column(length: 255, nullable: true)
    ]
    public ?string $remote_url = null;

    #[
        OA\Property(example: "stream"),
        ORM\Column(type: 'string', length: 25, nullable: true, enumType: PlaylistRemoteTypes::class)
    ]
    public ?PlaylistRemoteTypes $remote_type;

    #[
        OA\Property(
            description: "The total time (in seconds) that Liquidsoap should buffer remote URL streams.",
            example: 0
        ),
        ORM\Column(name: 'remote_timeout', type: 'smallint')
    ]
    public int $remote_buffer = 0;

    #[
        OA\Property(example: true),
        ORM\Column
    ]
    public bool $is_enabled = true;

    #[
        OA\Property(
            description: "If yes, do not send jingle metadata to AutoDJ or trigger web hooks.",
            example: false
        ),
        ORM\Column
    ]
    public bool $is_jingle = false;

    #[
        OA\Property(example: 5),
        ORM\Column(type: 'smallint')
    ]
    public int $play_per_songs = 0;

    #[
        OA\Property(example: 120),
        ORM\Column(type: 'smallint')
    ]
    public int $play_per_minutes = 0;

    #[
        OA\Property(example: 15),
        ORM\Column(type: 'smallint')
    ]
    public int $play_per_hour_minute = 0 {
        set {
            if ($value > 59 || $value < 0) {
                $value = 0;
            }

            $this->play_per_hour_minute = $value;
        }
    }

    #[
        OA\Property(
            description: "The relative weight of the playlist. Larger numbers play more often than playlists "
            . "with lower number weights.",
            example: 3,
        ),
        ORM\Column(type: 'smallint')
    ]
    public int $weight = self::DEFAULT_WEIGHT {
        get => ($this->weight >= 1) ? $this->weight : self::DEFAULT_WEIGHT;
    }

    #[
        OA\Property(example: true),
        ORM\Column
    ]
    public bool $include_in_requests = true;

    #[
        OA\Property(
            description: "Whether this playlist's media is included in 'on demand' download/streaming if enabled.",
            example: true
        ),
        ORM\Column
    ]
    public bool $include_in_on_demand = false;

    #[ORM\Column(name: 'backend_options', length: 255, nullable: true)]
    private ?string $backend_options_raw = '';

    #[OA\Property(
        items: new OA\Items(type: 'string'),
        example: "interrupt,loop_once,single_track,merge"
    )]
    public array $backend_options {
        get => explode(',', $this->backend_options_raw ?? '');
        set {
            $this->backend_options_raw = implode(',', array_filter($value));
        }
    }

    public function backendInterruptOtherSongs(): bool
    {
        return in_array(self::OPTION_INTERRUPT_OTHER_SONGS, $this->backend_options, true);
    }

    public function backendMerge(): bool
    {
        return in_array(self::OPTION_MERGE, $this->backend_options, true);
    }

    public function backendPlaySingleTrack(): bool
    {
        return in_array(self::OPTION_PLAY_SINGLE_TRACK, $this->backend_options, true);
    }

    public function backendPrioritizeOverRequests(): bool
    {
        return in_array(self::OPTION_PRIORITIZE_OVER_REQUESTS, $this->backend_options, true);
    }

    #[
        OA\Property(example: true),
        ORM\Column
    ]
    public bool $avoid_duplicates = true;

    #[
        ORM\Column(type: 'datetime_immutable', precision: 6, nullable: true),
        Attributes\AuditIgnore
    ]
    public ?DateTimeImmutable $played_at = null {
        set (DateTimeImmutable|string|int|null $value) => Time::toNullableUtcCarbonImmutable($value);
    }

    #[
        ORM\Column(type: 'datetime_immutable', precision: 6, nullable: true),
        Attributes\AuditIgnore
    ]
    public ?DateTimeImmutable $queue_reset_at = null {
        set (DateTimeImmutable|string|int|null $value) => Time::toNullableUtcCarbonImmutable($value);
    }

    /** @var Collection<int, StationPlaylistMedia> */
    #[
        ORM\OneToMany(targetEntity: StationPlaylistMedia::class, mappedBy: 'playlist', fetch: 'EXTRA_LAZY'),
        ORM\OrderBy(['weight' => 'ASC'])
    ]
    public private(set) Collection $media_items;

    /** @var Collection<int, StationPlaylistFolder> */
    #[
        ORM\OneToMany(targetEntity: StationPlaylistFolder::class, mappedBy: 'playlist', fetch: 'EXTRA_LAZY')
    ]
    public private(set) Collection $folders;

    /** @var Collection<int, StationSchedule> */
    #[
        OA\Property(type: "array", items: new OA\Items()),
        ORM\OneToMany(targetEntity: StationSchedule::class, mappedBy: 'playlist', fetch: 'EXTRA_LAZY'),
        DeepNormalize(true),
        Serializer\MaxDepth(1)
    ]
    public private(set) Collection $schedule_items;

    /** @var Collection<int, Podcast> */
    #[
        OA\Property(type: "array", items: new OA\Items()),
        ORM\OneToMany(targetEntity: Podcast::class, mappedBy: 'playlist', fetch: 'EXTRA_LAZY'),
        DeepNormalize(true),
        Serializer\MaxDepth(1)
    ]
    public private(set) Collection $podcasts;

    public function __construct(Station $station)
    {
        $this->station = $station;

        $this->type = PlaylistTypes::default();
        $this->source = PlaylistSources::Songs;
        $this->order = PlaylistOrders::Shuffle;
        $this->remote_type = PlaylistRemoteTypes::Stream;

        $this->media_items = new ArrayCollection();
        $this->folders = new ArrayCollection();
        $this->schedule_items = new ArrayCollection();
        $this->podcasts = new ArrayCollection();
    }

    /**
     * Indicates whether this playlist can be used as a valid source of requestable media.
     */
    public function isRequestable(): bool
    {
        return ($this->is_enabled && $this->include_in_requests);
    }

    /**
     * Indicates whether a playlist is enabled and has content which can be scheduled by an AutoDJ scheduler.
     *
     * @param bool $interrupting Whether determining "playability" for an interrupting queue or a regular one.
     */
    public function isPlayable(bool $interrupting = false): bool
    {
        if (!$this->is_enabled) {
            return false;
        }

        if ($interrupting !== $this->backendInterruptOtherSongs()) {
            return false;
        }

        if (PlaylistSources::Songs === $this->source) {
            return $this->media_items->count() > 0;
        }

        // Remote stream playlists aren't supported by the AzuraCast AutoDJ.
        return PlaylistRemoteTypes::Playlist === $this->remote_type;
    }

    public function __clone()
    {
        $this->played_at = null;
        $this->queue_reset_at = null;

        $this->media_items = new ArrayCollection();
        $this->folders = new ArrayCollection();
        $this->schedule_items = new ArrayCollection();
        $this->podcasts = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->station . ' Playlist: ' . $this->name;
    }

    public static function generateShortName(string $str): string
    {
        $str = File::sanitizeFileName($str);

        return (is_numeric($str))
            ? 'playlist_' . $str
            : $str;
    }
}
