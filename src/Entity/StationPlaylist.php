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

/**
 * @ORM\Table(name="station_playlists")
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 *
 * @AuditLog\Auditable
 *
 * @OA\Schema(type="object")
 */
class StationPlaylist
{
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

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     *
     * @OA\Property(example=1)
     * @var int|null
     */
    protected $id;

    /**
     * @ORM\Column(name="station_id", type="integer")
     * @var int
     */
    protected $station_id;

    /**
     * @ORM\ManyToOne(targetEntity="Station", inversedBy="playlists")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="station_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     * @var Station
     */
    protected $station;

    /**
     * @ORM\Column(name="name", type="string", length=200)
     *
     * @Assert\NotBlank()
     * @OA\Property(example="Test Playlist")
     *
     * @var string
     */
    protected $name;

    /**
     * @ORM\Column(name="type", type="string", length=50)
     *
     * @Assert\Choice(choices={"default", "once_per_x_songs", "once_per_x_minutes", "once_per_hour", "custom"})
     * @OA\Property(example="default")
     *
     * @var string
     */
    protected $type = self::TYPE_DEFAULT;

    /**
     * @ORM\Column(name="source", type="string", length=50)
     *
     * @Assert\Choice(choices={"songs", "remote_url"})
     * @OA\Property(example="songs")
     *
     * @var string
     */
    protected $source = self::SOURCE_SONGS;

    /**
     * @ORM\Column(name="playback_order", type="string", length=50)
     *
     * @Assert\Choice(choices={"random", "shuffle", "sequential"})
     * @OA\Property(example="shuffle")
     *
     * @var string
     */
    protected $order = self::ORDER_SHUFFLE;

    /**
     * @ORM\Column(name="remote_url", type="string", length=255, nullable=true)
     *
     * @OA\Property(example="http://remote-url.example.com/stream.mp3")
     *
     * @var string|null
     */
    protected $remote_url;

    /**
     * @ORM\Column(name="remote_type", type="string", length=25, nullable=true)
     *
     * @Assert\Choice(choices={"stream", "playlist"})
     * @OA\Property(example="stream")
     *
     * @var string|null
     */
    protected $remote_type = self::REMOTE_TYPE_STREAM;

    /**
     * @ORM\Column(name="remote_timeout", type="smallint")
     *
     * @OA\Property(example=0)
     *
     * @var int The total time (in seconds) that Liquidsoap should buffer remote URL streams.
     */
    protected $remote_buffer = 0;

    /**
     * @ORM\Column(name="is_enabled", type="boolean")
     *
     * @OA\Property(example=true)
     *
     * @var bool
     */
    protected $is_enabled = true;

    /**
     * @ORM\Column(name="is_jingle", type="boolean")
     *
     * @OA\Property(example=false)
     *
     * @var bool If yes, do not send jingle metadata to AutoDJ or trigger web hooks.
     */
    protected $is_jingle = false;

    /**
     * @ORM\Column(name="play_per_songs", type="smallint")
     *
     * @OA\Property(example=5)
     *
     * @var int
     */
    protected $play_per_songs = 0;

    /**
     * @ORM\Column(name="play_per_minutes", type="smallint")
     *
     * @OA\Property(example=120)
     *
     * @var int
     */
    protected $play_per_minutes = 0;

    /**
     * @ORM\Column(name="play_per_hour_minute", type="smallint")
     *
     * @OA\Property(example=15)
     *
     * @var int
     */
    protected $play_per_hour_minute = 0;

    /**
     * @ORM\Column(name="weight", type="smallint")
     *
     * @OA\Property(example=3)
     *
     * @var int
     */
    protected $weight = self::DEFAULT_WEIGHT;

    /**
     * @ORM\Column(name="include_in_requests", type="boolean")
     *
     * @OA\Property(example=true)
     *
     * @var bool
     */
    protected $include_in_requests = true;

    /**
     * @ORM\Column(name="include_in_on_demand", type="boolean")
     *
     * @OA\Property(example=true)
     *
     * @var bool Whether this playlist's media is included in "on demand" download/streaming if enabled.
     */
    protected $include_in_on_demand = false;

    /**
     * @ORM\Column(name="include_in_automation", type="boolean")
     *
     * @OA\Property(example=false)
     *
     * @var bool
     */
    protected $include_in_automation = false;

    /**
     * @ORM\Column(name="backend_options", type="string", length=255, nullable=true)
     *
     * @OA\Property(example="interrupt,loop_once,single_track,merge")
     *
     * @var string
     */
    protected $backend_options = '';

    /**
     * @ORM\Column(name="avoid_duplicates", type="boolean")
     *
     * @OA\Property(example=true)
     *
     * @var bool
     */
    protected $avoid_duplicates = true;

    /**
     * @ORM\Column(name="played_at", type="integer")
     * @AuditLog\AuditIgnore
     *
     * @var int The UNIX timestamp at which a track from this playlist was last played.
     */
    protected $played_at = 0;

    /**
     * @ORM\Column(name="queue", type="array", nullable=true)
     * @AuditLog\AuditIgnore
     *
     * @var array|null The current queue of unplayed songs for this playlist.
     */
    protected $queue;

    /**
     * @ORM\OneToMany(targetEntity="StationPlaylistMedia", mappedBy="playlist", fetch="EXTRA_LAZY")
     * @ORM\OrderBy({"weight" = "ASC"})
     * @var Collection
     */
    protected $media_items;

    /**
     * @ORM\OneToMany(targetEntity="StationSchedule", mappedBy="playlist")
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

        $this->media_items = new ArrayCollection();
        $this->schedule_items = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStation(): Station
    {
        return $this->station;
    }

    /**
     * @AuditLog\AuditIdentifier
     */
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
        // Reset the playback queue if source is changed.
        if ($source !== $this->source) {
            $this->queue = null;
        }

        $this->source = $source;
    }

    public function getOrder(): string
    {
        return $this->order;
    }

    public function setOrder(string $order): void
    {
        // Reset the playback queue if order is changed.
        if ($order !== $this->order) {
            $this->queue = null;
        }

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

    /**
     * @return mixed[]|null
     */
    public function getQueue(): ?array
    {
        if (null === $this->queue) {
            return null;
        }

        // Ensure queue is always formatted correctly.
        $newQueue = [];
        foreach ($this->queue as $media) {
            $newQueue[$media['id']] = $media;
        }
        return $newQueue;
    }

    public function setQueue(?array $queue): void
    {
        $this->queue = $queue;
    }

    public function removeFromQueue(StationMedia $media): void
    {
        $queue = $this->getQueue();

        if (null !== $queue) {
            unset($queue[$media->getId()]);
            $this->queue = $queue;
        }
    }

    public function addToQueue(StationMedia $media): void
    {
        $queue = $this->getQueue();
        if (null === $queue) {
            return;
        }

        $queue[$media->getId()] = [
            'id' => $media->getId(),
            'song_id' => $media->getSongId(),
            'artist' => $media->getArtist(),
            'title' => $media->getTitle(),
        ];

        if (self::ORDER_SEQUENTIAL !== $this->getOrder()) {
            shuffle($queue);

            $newQueue = [];
            foreach ($queue as $row) {
                $newQueue[$row['id']] = $row;
            }
            $queue = $newQueue;
        }

        $this->setQueue($queue);
    }

    /**
     * @return Collection|StationPlaylistMedia[]
     */
    public function getMediaItems(): Collection
    {
        return $this->media_items;
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
        $settings = \App\Settings::getInstance();
        if (!$settings->enableAdvancedFeatures()) {
            return [];
        }

        return explode(',', $this->backend_options);
    }

    /**
     * @param array $backend_options
     */
    public function setBackendOptions($backend_options): void
    {
        $this->backend_options = implode(',', (array)$backend_options);
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

    /**
     * Export the playlist into a reusable format.
     *
     * @param string $file_format
     * @param bool $absolute_paths
     * @param bool $with_annotations
     */
    public function export(
        $file_format = 'pls',
        $absolute_paths = false,
        $with_annotations = false
    ): string {
        if ($absolute_paths) {
            $mediaStorage = $this->station->getMediaStorageLocation();
            if (!$mediaStorage->isLocal()) {
                throw new \RuntimeException('Media is not hosted locally on this system.');
            }

            $media_path = $mediaStorage->getPath() . '/';
        } else {
            $media_path = '';
        }

        switch ($file_format) {
            case 'm3u':
                $playlist_file = [];
                foreach ($this->media_items as $media_item) {
                    $media_file = $media_item->getMedia();
                    $media_file_path = $media_path . $media_file->getPath();
                    $playlist_file[] = $media_file_path;
                }

                return implode("\n", $playlist_file);

            case 'pls':
            default:
                $playlist_file = [
                    '[playlist]',
                ];

                $i = 0;
                foreach ($this->media_items as $media_item) {
                    $i++;

                    $media_file = $media_item->getMedia();
                    $media_file_path = $media_path . $media_file->getPath();
                    $playlist_file[] = 'File' . $i . '=' . $media_file_path;
                    $playlist_file[] = 'Title' . $i . '=' . $media_file->getArtist() . ' - ' . $media_file->getTitle();
                    $playlist_file[] = 'Length' . $i . '=' . $media_file->getLength();
                    $playlist_file[] = '';
                }

                $playlist_file[] = 'NumberOfEntries=' . $i;
                $playlist_file[] = 'Version=2';

                return implode("\n", $playlist_file);
        }
    }
}
