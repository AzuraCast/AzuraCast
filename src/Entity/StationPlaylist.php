<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Enums\PlaylistOrders;
use App\Entity\Enums\PlaylistRemoteTypes;
use App\Entity\Enums\PlaylistSources;
use App\Entity\Enums\PlaylistTypes;
use App\Normalizer\Attributes\DeepNormalize;
use App\Utilities\File;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use OpenApi\Attributes as OA;
use Stringable;
use Symfony\Component\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

#[
    OA\Schema(type: "object"),
    ORM\Entity,
    ORM\Table(name: 'station_playlists'),
    ORM\HasLifecycleCallbacks,
    Attributes\Auditable
]
class StationPlaylist implements
    Stringable,
    Interfaces\StationCloneAwareInterface,
    Interfaces\IdentifiableEntityInterface
{
    use Traits\HasAutoIncrementId;
    use Traits\TruncateStrings;

    public const DEFAULT_WEIGHT = 3;
    public const DEFAULT_REMOTE_BUFFER = 20;

    public const OPTION_INTERRUPT_OTHER_SONGS = 'interrupt';
    public const OPTION_PLAY_SINGLE_TRACK = 'single_track';
    public const OPTION_MERGE = 'merge';

    #[
        ORM\ManyToOne(inversedBy: 'playlists'),
        ORM\JoinColumn(name: 'station_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')
    ]
    protected Station $station;

    #[ORM\Column(nullable: false, insertable: false, updatable: false)]
    protected int $station_id;

    #[
        OA\Property(example: "Test Playlist"),
        ORM\Column(length: 200),
        Assert\NotBlank
    ]
    protected string $name;

    #[
        OA\Property(example: "default"),
        ORM\Column(type: 'string', length: 50, enumType: PlaylistTypes::class)
    ]
    protected PlaylistTypes $type;

    #[
        OA\Property(example: "songs"),
        ORM\Column(type: 'string', length: 50, enumType: PlaylistSources::class)
    ]
    protected PlaylistSources $source;

    #[
        OA\Property(example: "shuffle"),
        ORM\Column(name: 'playback_order', type: 'string', length: 50, enumType: PlaylistOrders::class)
    ]
    protected PlaylistOrders $order;

    #[
        OA\Property(example: "https://remote-url.example.com/stream.mp3"),
        ORM\Column(length: 255, nullable: true)
    ]
    protected ?string $remote_url = null;

    #[
        OA\Property(example: "stream"),
        ORM\Column(type: 'string', length: 25, nullable: true, enumType: PlaylistRemoteTypes::class)
    ]
    protected ?PlaylistRemoteTypes $remote_type;

    #[
        OA\Property(
            description: "The total time (in seconds) that Liquidsoap should buffer remote URL streams.",
            example: 0
        ),
        ORM\Column(name: 'remote_timeout', type: 'smallint')
    ]
    protected int $remote_buffer = 0;

    #[
        OA\Property(example: true),
        ORM\Column
    ]
    protected bool $is_enabled = true;

    #[
        OA\Property(
            description: "If yes, do not send jingle metadata to AutoDJ or trigger web hooks.",
            example: false
        ),
        ORM\Column
    ]
    protected bool $is_jingle = false;

    #[
        OA\Property(example: 5),
        ORM\Column(type: 'smallint')
    ]
    protected int $play_per_songs = 0;

    #[
        OA\Property(example: 120),
        ORM\Column(type: 'smallint')
    ]
    protected int $play_per_minutes = 0;

    #[
        OA\Property(example: 15),
        ORM\Column(type: 'smallint')
    ]
    protected int $play_per_hour_minute = 0;

    #[
        OA\Property(example: 3),
        ORM\Column(type: 'smallint')
    ]
    protected int $weight = self::DEFAULT_WEIGHT;

    #[
        OA\Property(example: true),
        ORM\Column
    ]
    protected bool $include_in_requests = true;

    #[
        OA\Property(
            description: "Whether this playlist's media is included in 'on demand' download/streaming if enabled.",
            example: true
        ),
        ORM\Column
    ]
    protected bool $include_in_on_demand = false;

    #[
        OA\Property(example: "interrupt,loop_once,single_track,merge"),
        ORM\Column(length: 255, nullable: true)
    ]
    protected ?string $backend_options = '';

    #[
        OA\Property(example: true),
        ORM\Column
    ]
    protected bool $avoid_duplicates = true;

    #[
        ORM\Column,
        Attributes\AuditIgnore
    ]
    protected int $played_at = 0;

    #[
        ORM\Column,
        Attributes\AuditIgnore
    ]
    protected int $queue_reset_at = 0;

    /** @var Collection<int, StationPlaylistMedia> */
    #[
        ORM\OneToMany(mappedBy: 'playlist', targetEntity: StationPlaylistMedia::class, fetch: 'EXTRA_LAZY'),
        ORM\OrderBy(['weight' => 'ASC'])
    ]
    protected Collection $media_items;

    /** @var Collection<int, StationPlaylistFolder> */
    #[
        ORM\OneToMany(mappedBy: 'playlist', targetEntity: StationPlaylistFolder::class, fetch: 'EXTRA_LAZY')
    ]
    protected Collection $folders;

    /** @var Collection<int, StationSchedule> */
    #[
        OA\Property(type: "array", items: new OA\Items()),
        ORM\OneToMany(mappedBy: 'playlist', targetEntity: StationSchedule::class, fetch: 'EXTRA_LAZY'),
        DeepNormalize(true),
        Serializer\MaxDepth(1)
    ]
    protected Collection $schedule_items;

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
    }

    public function getStation(): Station
    {
        return $this->station;
    }

    public function setStation(Station $station): void
    {
        $this->station = $station;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $this->truncateString($name, 200);
    }

    public function getShortName(): string
    {
        return self::generateShortName($this->name);
    }

    public function getType(): PlaylistTypes
    {
        return $this->type;
    }

    public function setType(PlaylistTypes $type): void
    {
        $this->type = $type;
    }

    public function getSource(): PlaylistSources
    {
        return $this->source;
    }

    public function setSource(PlaylistSources $source): void
    {
        $this->source = $source;

        if (PlaylistSources::RemoteUrl === $source) {
            $this->type = PlaylistTypes::Standard;
        }
    }

    public function getOrder(): PlaylistOrders
    {
        return $this->order;
    }

    public function setOrder(PlaylistOrders $order): void
    {
        $this->order = $order;
    }

    public function getRemoteUrl(): ?string
    {
        return $this->remote_url;
    }

    public function setRemoteUrl(?string $remoteUrl): void
    {
        $this->remote_url = $remoteUrl;
    }

    public function getRemoteType(): ?PlaylistRemoteTypes
    {
        return $this->remote_type;
    }

    public function setRemoteType(?PlaylistRemoteTypes $remoteType): void
    {
        $this->remote_type = $remoteType;
    }

    public function getRemoteBuffer(): int
    {
        return $this->remote_buffer;
    }

    public function setRemoteBuffer(int $remoteBuffer): void
    {
        $this->remote_buffer = $remoteBuffer;
    }

    public function getIsEnabled(): bool
    {
        return $this->is_enabled;
    }

    public function setIsEnabled(bool $isEnabled): void
    {
        $this->is_enabled = $isEnabled;
    }

    public function getIsJingle(): bool
    {
        return $this->is_jingle;
    }

    public function setIsJingle(bool $isJingle): void
    {
        $this->is_jingle = $isJingle;
    }

    public function getWeight(): int
    {
        if ($this->weight < 1) {
            return self::DEFAULT_WEIGHT;
        }

        return $this->weight;
    }

    public function setWeight(int $weight): void
    {
        $this->weight = $weight;
    }

    public function getIncludeInRequests(): bool
    {
        return $this->include_in_requests;
    }

    public function setIncludeInRequests(bool $includeInRequests): void
    {
        $this->include_in_requests = $includeInRequests;
    }

    public function getIncludeInOnDemand(): bool
    {
        return $this->include_in_on_demand;
    }

    public function setIncludeInOnDemand(bool $includeInOnDemand): void
    {
        $this->include_in_on_demand = $includeInOnDemand;
    }

    /**
     * Indicates whether this playlist can be used as a valid source of requestable media.
     */
    public function isRequestable(): bool
    {
        return ($this->is_enabled && $this->include_in_requests);
    }

    public function getAvoidDuplicates(): bool
    {
        return $this->avoid_duplicates;
    }

    public function setAvoidDuplicates(bool $avoidDuplicates): void
    {
        $this->avoid_duplicates = $avoidDuplicates;
    }

    public function getPlayedAt(): int
    {
        return $this->played_at;
    }

    public function setPlayedAt(int $playedAt): void
    {
        $this->played_at = $playedAt;
    }

    public function getQueueResetAt(): int
    {
        return $this->queue_reset_at;
    }

    public function setQueueResetAt(int $queueResetAt): void
    {
        $this->queue_reset_at = $queueResetAt;
    }

    /**
     * @return Collection<int, StationPlaylistMedia>
     */
    public function getMediaItems(): Collection
    {
        return $this->media_items;
    }

    /**
     * @return Collection<int, StationPlaylistFolder>
     */
    public function getFolders(): Collection
    {
        return $this->folders;
    }

    /**
     * @return Collection<int, StationSchedule>
     */
    public function getScheduleItems(): Collection
    {
        return $this->schedule_items;
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

        if (PlaylistSources::Songs === $this->getSource()) {
            return $this->media_items->count() > 0;
        }

        // Remote stream playlists aren't supported by the AzuraCast AutoDJ.
        return PlaylistRemoteTypes::Playlist === $this->getRemoteType();
    }

    /**
     * @return string[]
     */
    public function getBackendOptions(): array
    {
        return explode(',', $this->backend_options ?? '');
    }

    /**
     * @param array $backendOptions
     */
    public function setBackendOptions(array $backendOptions): void
    {
        $this->backend_options = implode(',', $backendOptions);
    }

    public function backendInterruptOtherSongs(): bool
    {
        $backendOptions = $this->getBackendOptions();
        return in_array(self::OPTION_INTERRUPT_OTHER_SONGS, $backendOptions, true);
    }

    public function backendMerge(): bool
    {
        $backendOptions = $this->getBackendOptions();
        return in_array(self::OPTION_MERGE, $backendOptions, true);
    }

    public function backendPlaySingleTrack(): bool
    {
        $backendOptions = $this->getBackendOptions();
        return in_array(self::OPTION_PLAY_SINGLE_TRACK, $backendOptions, true);
    }

    public function getPlayPerHourMinute(): int
    {
        return $this->play_per_hour_minute;
    }

    public function setPlayPerHourMinute(int $playPerHourMinute): void
    {
        if ($playPerHourMinute > 59 || $playPerHourMinute < 0) {
            $playPerHourMinute = 0;
        }

        $this->play_per_hour_minute = $playPerHourMinute;
    }

    public function getPlayPerSongs(): int
    {
        return $this->play_per_songs;
    }

    public function setPlayPerSongs(int $playPerSongs): void
    {
        $this->play_per_songs = $playPerSongs;
    }

    public function getPlayPerMinutes(): int
    {
        return $this->play_per_minutes;
    }

    public function setPlayPerMinutes(
        int $playPerMinutes
    ): void {
        $this->play_per_minutes = $playPerMinutes;
    }

    public function __clone()
    {
        $this->played_at = 0;
        $this->queue_reset_at = 0;
    }

    public function __toString(): string
    {
        return $this->getStation() . ' Playlist: ' . $this->getName();
    }

    public static function generateShortName(string $str): string
    {
        $str = File::sanitizeFileName($str);

        return (is_numeric($str))
            ? 'playlist_' . $str
            : $str;
    }
}
