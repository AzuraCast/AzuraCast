<?php

declare(strict_types=1);

namespace App\Entity;

use App\Normalizer\Attributes\DeepNormalize;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use OpenApi\Annotations as OA;
use Stringable;
use Symfony\Component\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

/** @OA\Schema(type="object") */
#[
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

    public const TYPE_DEFAULT = 'default';
    public const TYPE_ONCE_PER_X_SONGS = 'once_per_x_songs';
    public const TYPE_ONCE_PER_X_MINUTES = 'once_per_x_minutes';
    public const TYPE_ONCE_PER_HOUR = 'once_per_hour';
    public const TYPE_ADVANCED = 'custom';

    public const SOURCE_SONGS = 'songs';
    public const SOURCE_REMOTE_URL = 'remote_url';

    public const REMOTE_TYPE_STREAM = 'stream';
    public const REMOTE_TYPE_PLAYLIST = 'playlist';

    public const ORDER_RANDOM = 'random';
    public const ORDER_SHUFFLE = 'shuffle';
    public const ORDER_SEQUENTIAL = 'sequential';

    public const OPTION_INTERRUPT_OTHER_SONGS = 'interrupt';
    public const OPTION_LOOP_PLAYLIST_ONCE = 'loop_once';
    public const OPTION_PLAY_SINGLE_TRACK = 'single_track';
    public const OPTION_MERGE = 'merge';

    #[ORM\Column(nullable: false)]
    protected int $station_id;

    #[ORM\ManyToOne(inversedBy: 'playlists')]
    #[ORM\JoinColumn(name: 'station_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected Station $station;

    /** @OA\Property(example="Test Playlist") */
    #[ORM\Column(length: 200)]
    #[Assert\NotBlank]
    protected string $name;

    /** @OA\Property(example="default") */
    #[ORM\Column(length: 50)]
    #[Assert\Choice(choices: [
        self::TYPE_DEFAULT,
        self::TYPE_ONCE_PER_X_SONGS,
        self::TYPE_ONCE_PER_X_MINUTES,
        self::TYPE_ONCE_PER_HOUR,
        self::TYPE_ADVANCED,
    ])]
    protected string $type = self::TYPE_DEFAULT;

    /** @OA\Property(example="songs") */
    #[ORM\Column(length: 50)]
    #[Assert\Choice(choices: [self::SOURCE_SONGS, self::SOURCE_REMOTE_URL])]
    protected string $source = self::SOURCE_SONGS;

    /** @OA\Property(example="shuffle") */
    #[ORM\Column(name: 'playback_order', length: 50)]
    #[Assert\Choice(choices: [self::ORDER_RANDOM, self::ORDER_SHUFFLE, self::ORDER_SEQUENTIAL])]
    protected string $order = self::ORDER_SHUFFLE;

    /** @OA\Property(example="https://remote-url.example.com/stream.mp3") */
    #[ORM\Column(length: 255, nullable: true)]
    protected ?string $remote_url = null;

    /** @OA\Property(example="stream") */
    #[ORM\Column(length: 25, nullable: true)]
    #[Assert\Choice(choices: [self::REMOTE_TYPE_STREAM, self::REMOTE_TYPE_PLAYLIST])]
    protected ?string $remote_type = self::REMOTE_TYPE_STREAM;

    /**
     * @OA\Property(
     *     description="The total time (in seconds) that Liquidsoap should buffer remote URL streams.",
     *     example=0
     * )
     */
    #[ORM\Column(name: 'remote_timeout', type: 'smallint')]
    protected int $remote_buffer = 0;

    /** @OA\Property(example=true) */
    #[ORM\Column]
    protected bool $is_enabled = true;

    /**
     * @OA\Property(
     *     description="If yes, do not send jingle metadata to AutoDJ or trigger web hooks.",
     *     example=false
     * )
     */
    #[ORM\Column]
    protected bool $is_jingle = false;

    /** @OA\Property(example=5) */
    #[ORM\Column(type: 'smallint')]
    protected int $play_per_songs = 0;

    /** @OA\Property(example=120) */
    #[ORM\Column(type: 'smallint')]
    protected int $play_per_minutes = 0;

    /** @OA\Property(example=15) */
    #[ORM\Column(type: 'smallint')]
    protected int $play_per_hour_minute = 0;

    /** @OA\Property(example=3) */
    #[ORM\Column(type: 'smallint')]
    protected int $weight = self::DEFAULT_WEIGHT;

    /** @OA\Property(example=true) */
    #[ORM\Column]
    protected bool $include_in_requests = true;

    /**
     * @OA\Property(
     *     description="Whether this playlist's media is included in 'on demand' download/streaming if enabled.",
     *     example=true
     * )
     */
    #[ORM\Column]
    protected bool $include_in_on_demand = false;

    /** @OA\Property(example=false) */
    #[ORM\Column]
    protected bool $include_in_automation = false;

    /** @OA\Property(example="interrupt,loop_once,single_track,merge") */
    #[ORM\Column(length: 255, nullable: true)]
    protected ?string $backend_options = '';

    /** @OA\Property(example=true) */
    #[ORM\Column]
    protected bool $avoid_duplicates = true;

    #[ORM\Column]
    #[Attributes\AuditIgnore]
    protected int $played_at = 0;

    #[ORM\Column]
    #[Attributes\AuditIgnore]
    protected int $queue_reset_at = 0;

    #[ORM\OneToMany(mappedBy: 'playlist', targetEntity: StationPlaylistMedia::class, fetch: 'EXTRA_LAZY')]
    #[ORM\OrderBy(['weight' => 'ASC'])]
    protected Collection $media_items;

    #[ORM\OneToMany(mappedBy: 'playlist', targetEntity: StationPlaylistFolder::class, fetch: 'EXTRA_LAZY')]
    protected Collection $folders;

    /**
     * @OA\Property(
     *     type="array",
     *     @OA\Items()
     * )
     */
    #[ORM\OneToMany(mappedBy: 'playlist', targetEntity: StationSchedule::class, fetch: 'EXTRA_LAZY')]
    #[DeepNormalize(true)]
    #[Serializer\MaxDepth(1)]
    protected Collection $schedule_items;

    public function __construct(Station $station)
    {
        $this->station = $station;

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
        return Station::getStationShortName($this->name);
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function setSource(string $source): void
    {
        $this->source = $source;
    }

    public function getOrder(): string
    {
        return $this->order;
    }

    public function setOrder(string $order): void
    {
        $this->order = $order;
    }

    public function getRemoteUrl(): ?string
    {
        return $this->remote_url;
    }

    public function setRemoteUrl(?string $remote_url): void
    {
        $this->remote_url = $remote_url;
    }

    public function getRemoteType(): ?string
    {
        return $this->remote_type;
    }

    public function setRemoteType(?string $remote_type): void
    {
        $this->remote_type = $remote_type;
    }

    public function getRemoteBuffer(): int
    {
        return $this->remote_buffer;
    }

    public function setRemoteBuffer(int $remote_buffer): void
    {
        $this->remote_buffer = $remote_buffer;
    }

    public function getIsEnabled(): bool
    {
        return $this->is_enabled;
    }

    public function setIsEnabled(bool $is_enabled): void
    {
        $this->is_enabled = $is_enabled;
    }

    public function isJingle(): bool
    {
        return $this->is_jingle;
    }

    public function setIsJingle(bool $is_jingle): void
    {
        $this->is_jingle = $is_jingle;
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

    public function setIncludeInRequests(bool $include_in_requests): void
    {
        $this->include_in_requests = $include_in_requests;
    }

    public function getIncludeInOnDemand(): bool
    {
        return $this->include_in_on_demand;
    }

    public function setIncludeInOnDemand(bool $include_in_on_demand): void
    {
        $this->include_in_on_demand = $include_in_on_demand;
    }

    /**
     * Indicates whether this playlist can be used as a valid source of requestable media.
     */
    public function isRequestable(): bool
    {
        return ($this->is_enabled && $this->include_in_requests);
    }

    public function getIncludeInAutomation(): bool
    {
        return $this->include_in_automation;
    }

    public function setIncludeInAutomation(bool $include_in_automation): void
    {
        $this->include_in_automation = $include_in_automation;
    }

    public function getAvoidDuplicates(): bool
    {
        return $this->avoid_duplicates;
    }

    public function setAvoidDuplicates(bool $avoid_duplicates): void
    {
        $this->avoid_duplicates = $avoid_duplicates;
    }

    public function getPlayedAt(): int
    {
        return $this->played_at;
    }

    public function setPlayedAt(int $played_at): void
    {
        $this->played_at = $played_at;
    }

    public function getQueueResetAt(): int
    {
        return $this->queue_reset_at;
    }

    public function setQueueResetAt(int $queue_reset_at): void
    {
        $this->queue_reset_at = $queue_reset_at;
    }

    /**
     * @return Collection|StationPlaylistMedia[]
     */
    public function getMediaItems(): Collection
    {
        return $this->media_items;
    }

    /**
     * @return Collection|StationPlaylistFolder[]
     */
    public function getFolders(): Collection
    {
        return $this->folders;
    }

    /**
     * @return Collection|StationSchedule[]
     */
    public function getScheduleItems(): Collection
    {
        return $this->schedule_items;
    }

    /**
     * Indicates whether a playlist is enabled and has content which can be scheduled by an AutoDJ scheduler.
     */
    public function isPlayable(): bool
    {
        // Any "advanced" settings are not managed by AzuraCast AutoDJ.
        if (
            !$this->is_enabled
            || $this->backendInterruptOtherSongs()
            || $this->backendMerge()
            || $this->backendLoopPlaylistOnce()
            || $this->backendPlaySingleTrack()
        ) {
            return false;
        }

        if (self::SOURCE_SONGS === $this->source) {
            return $this->media_items->count() > 0;
        }

        // Remote stream playlists aren't supported by the AzuraCast AutoDJ.
        return self::REMOTE_TYPE_PLAYLIST === $this->remote_type;
    }

    /**
     * @return string[]
     */
    public function getBackendOptions(): array
    {
        return explode(',', $this->backend_options ?? '');
    }

    /**
     * @param array $backend_options
     */
    public function setBackendOptions(array $backend_options): void
    {
        $this->backend_options = implode(',', $backend_options);
    }

    public function backendInterruptOtherSongs(): bool
    {
        $backend_options = $this->getBackendOptions();
        return in_array(self::OPTION_INTERRUPT_OTHER_SONGS, $backend_options, true);
    }

    public function backendMerge(): bool
    {
        $backend_options = $this->getBackendOptions();
        return in_array(self::OPTION_MERGE, $backend_options, true);
    }

    public function backendLoopPlaylistOnce(): bool
    {
        $backend_options = $this->getBackendOptions();
        return in_array(self::OPTION_LOOP_PLAYLIST_ONCE, $backend_options, true);
    }

    public function backendPlaySingleTrack(): bool
    {
        $backend_options = $this->getBackendOptions();
        return in_array(self::OPTION_PLAY_SINGLE_TRACK, $backend_options, true);
    }

    public function getPlayPerHourMinute(): int
    {
        return $this->play_per_hour_minute;
    }

    public function setPlayPerHourMinute(int $play_per_hour_minute): void
    {
        if ($play_per_hour_minute > 59 || $play_per_hour_minute < 0) {
            $play_per_hour_minute = 0;
        }

        $this->play_per_hour_minute = $play_per_hour_minute;
    }

    public function getPlayPerSongs(): int
    {
        return $this->play_per_songs;
    }

    public function setPlayPerSongs(int $play_per_songs): void
    {
        $this->play_per_songs = $play_per_songs;
    }

    public function getPlayPerMinutes(): int
    {
        return $this->play_per_minutes;
    }

    public function setPlayPerMinutes(
        int $play_per_minutes
    ): void {
        $this->play_per_minutes = $play_per_minutes;
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
}
