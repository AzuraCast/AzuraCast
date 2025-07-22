<?php

declare(strict_types=1);

namespace App\Entity;

use App\Doctrine\AbstractArrayEntity;
use App\Entity\Enums\StorageLocationAdapters;
use App\Entity\Enums\StorageLocationTypes;
use App\Entity\Interfaces\EntityGroupsInterface;
use App\Entity\Interfaces\IdentifiableEntityInterface;
use App\Environment;
use App\Radio\Enums\BackendAdapters;
use App\Radio\Enums\FrontendAdapters;
use App\Utilities\File;
use App\Utilities\Types;
use App\Validator\Constraints as AppAssert;
use Azura\Normalizer\Attributes\DeepNormalize;
use DateTimeZone;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use OpenApi\Attributes as OA;
use RuntimeException;
use Stringable;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * @phpstan-import-type ConfigData from AbstractArrayEntity
 */
#[
    OA\Schema(schema: "Station", type: "object"),
    ORM\Entity,
    ORM\Table(name: 'station'),
    ORM\Index(name: 'idx_short_name', columns: ['short_name']),
    ORM\HasLifecycleCallbacks,
    Attributes\Auditable,
    AppAssert\StationPortChecker,
    AppAssert\UniqueEntity(fields: ['short_name'])
]
final class Station implements Stringable, IdentifiableEntityInterface
{
    use Traits\HasAutoIncrementId;
    use Traits\TruncateStrings;
    use Traits\ValidateMaxBitrate;

    public const string PLAYLISTS_DIR = 'playlists';
    public const string CONFIG_DIR = 'config';
    public const string TEMP_DIR = 'temp';
    public const string HLS_DIR = 'hls';

    public const array NON_STORAGE_LOCATION_DIRS = [
        self::PLAYLISTS_DIR,
        self::CONFIG_DIR,
        self::TEMP_DIR,
        self::HLS_DIR,
    ];

    #[
        OA\Property(description: "The full display name of the station.", example: "AzuraTest Radio"),
        ORM\Column(length: 100, nullable: false),
        Assert\NotBlank,
        Serializer\Groups([EntityGroupsInterface::GROUP_GENERAL, EntityGroupsInterface::GROUP_ALL])
    ]
    public string $name = '' {
        set {
            $this->name = $this->truncateString($value, 100);

            if (empty($this->short_name) && !empty($value)) {
                $this->short_name = self::generateShortName($value);
            }
        }
    }

    #[
        OA\Property(
            description: "The URL-friendly name for the station, typically auto-generated from the full station name.",
            example: "azuratest_radio"
        ),
        ORM\Column(length: 100, nullable: false),
        Assert\NotBlank,
        Serializer\Groups([EntityGroupsInterface::GROUP_GENERAL, EntityGroupsInterface::GROUP_ALL])
    ]
    public string $short_name = '' {
        set {
            $shortName = trim($value);
            if (empty($shortName)) {
                $shortName = $this->name;
            }

            $shortName = self::generateShortName($shortName);

            $shortName = $this->truncateString($shortName, 100);
            if ($this->short_name !== $shortName) {
                $this->needs_restart = true;
            }
            $this->short_name = $shortName;
        }
    }

    #[
        OA\Property(
            description: "If set to 'false', prevents the station from broadcasting but leaves it in the database.",
            example: true
        ),
        ORM\Column,
        Serializer\Groups([EntityGroupsInterface::GROUP_ADMIN, EntityGroupsInterface::GROUP_ALL])
    ]
    public bool $is_enabled = true;

    #[
        OA\Property(
            description: "The frontend adapter (icecast,shoutcast,remote,etc)",
            example: "icecast"
        ),
        ORM\Column(type: 'string', length: 100, enumType: FrontendAdapters::class),
        Serializer\Groups([EntityGroupsInterface::GROUP_GENERAL, EntityGroupsInterface::GROUP_ALL])
    ]
    public FrontendAdapters $frontend_type;

    /**
     * @var ConfigData|null
     */
    #[ORM\Column(name: 'frontend_config', type: 'json', nullable: true)]
    private ?array $frontend_config_raw = null;

    #[
        OA\Property(
            description: "An array containing station-specific frontend configuration",
            type: "object"
        ),
        Serializer\Groups([EntityGroupsInterface::GROUP_GENERAL, EntityGroupsInterface::GROUP_ALL])
    ]
    public StationFrontendConfiguration $frontend_config {
        get => new StationFrontendConfiguration((array)$this->frontend_config_raw);
        set (StationFrontendConfiguration|array|null $value) {
            $this->frontend_config_raw = StationFrontendConfiguration::merge(
                $this->frontend_config_raw,
                $value
            );
        }
    }

    #[
        OA\Property(
            description: "The backend adapter (liquidsoap,etc)",
            example: "liquidsoap"
        ),
        ORM\Column(type: 'string', length: 100, enumType: BackendAdapters::class),
        Serializer\Groups([EntityGroupsInterface::GROUP_GENERAL, EntityGroupsInterface::GROUP_ALL])
    ]
    public BackendAdapters $backend_type;

    /**
     * @var ConfigData|null
     */
    #[ORM\Column(name: 'backend_config', type: 'json', nullable: true)]
    private ?array $backend_config_raw = null;

    #[
        OA\Property(
            description: "An array containing station-specific backend configuration",
            type: "object"
        ),
        Serializer\Groups([EntityGroupsInterface::GROUP_GENERAL, EntityGroupsInterface::GROUP_ALL])
    ]
    public StationBackendConfiguration $backend_config {
        get => new StationBackendConfiguration((array)$this->backend_config_raw);
        set (StationBackendConfiguration|array|null $value) {
            $this->backend_config_raw = StationBackendConfiguration::merge(
                $this->backend_config_raw,
                $value
            );
        }
    }

    #[Assert\Callback]
    public function hasValidBitrate(ExecutionContextInterface $context): void
    {
        $this->doValidateMaxBitrate(
            $context,
            $this->max_bitrate,
            $this->backend_config->record_streams_bitrate,
            'backend_config.record_streams_bitrate'
        );
    }

    #[
        ORM\Column(length: 150, nullable: true),
        Attributes\AuditIgnore
    ]
    public ?string $adapter_api_key = null;

    /**
     * Generate a random new adapter API key.
     */
    public function generateAdapterApiKey(): void
    {
        $this->adapter_api_key = bin2hex(random_bytes(50));
    }

    /**
     * Authenticate the supplied adapter API key.
     */
    public function validateAdapterApiKey(string $apiKey): bool
    {
        return hash_equals($apiKey, $this->adapter_api_key ?? '');
    }

    #[
        OA\Property(example: "A sample radio station."),
        ORM\Column(type: 'text', nullable: true),
        Serializer\Groups([EntityGroupsInterface::GROUP_GENERAL, EntityGroupsInterface::GROUP_ALL])
    ]
    public ?string $description = null;

    #[
        OA\Property(example: "https://demo.azuracast.com/"),
        ORM\Column(length: 255, nullable: true),
        Serializer\Groups([EntityGroupsInterface::GROUP_GENERAL, EntityGroupsInterface::GROUP_ALL])
    ]
    public ?string $url = null {
        set {
            $url = $this->truncateNullableString($value);

            if ($url !== $this->url) {
                $this->needs_restart = true;
            }

            $this->url = $url;
        }
    }

    #[
        OA\Property(example: "Various"),
        ORM\Column(length: 255, nullable: true),
        Serializer\Groups([EntityGroupsInterface::GROUP_GENERAL, EntityGroupsInterface::GROUP_ALL])
    ]
    public ?string $genre = null {
        set => $this->truncateNullableString($value);
    }

    #[
        OA\Property(example: "/var/azuracast/stations/azuratest_radio"),
        ORM\Column(length: 255, nullable: false),
        Serializer\Groups([EntityGroupsInterface::GROUP_ADMIN, EntityGroupsInterface::GROUP_ALL])
    ]
    public string $radio_base_dir {
        set {
            $newDir = Types::stringOrNull($value, true);
            if (null === $newDir) {
                $newDir = $this->short_name;
            }

            if (Path::isRelative($newDir)) {
                $newDir = Path::makeAbsolute(
                    $newDir,
                    Environment::getInstance()->getStationDirectory()
                );
            }

            $this->radio_base_dir = $this->truncateString($newDir);
        }
    }

    #[
        OA\Property(
            description: "Whether listeners can request songs to play on this station.",
            example: true
        ),
        ORM\Column,
        Serializer\Groups([EntityGroupsInterface::GROUP_GENERAL, EntityGroupsInterface::GROUP_ALL])
    ]
    public bool $enable_requests = false;

    #[
        OA\Property(example: 5),
        ORM\Column(nullable: true),
        Serializer\Groups([EntityGroupsInterface::GROUP_GENERAL, EntityGroupsInterface::GROUP_ALL])
    ]
    public ?int $request_delay = 5;

    #[
        OA\Property(example: 15),
        ORM\Column(nullable: true),
        Serializer\Groups([EntityGroupsInterface::GROUP_GENERAL, EntityGroupsInterface::GROUP_ALL])
    ]
    public ?int $request_threshold = 15;

    #[
        OA\Property(example: 0),
        ORM\Column(nullable: true, options: ['default' => 0]),
        Serializer\Groups([EntityGroupsInterface::GROUP_GENERAL, EntityGroupsInterface::GROUP_ALL])
    ]
    public ?int $disconnect_deactivate_streamer = 0;

    #[
        OA\Property(
            description: "Whether streamers are allowed to broadcast to this station at all.",
            example: false
        ),
        ORM\Column,
        Serializer\Groups([EntityGroupsInterface::GROUP_GENERAL, EntityGroupsInterface::GROUP_ALL])
    ]
    public bool $enable_streamers = false {
        set {
            if ($this->enable_streamers !== $value) {
                $this->needs_restart = true;
            }

            $this->enable_streamers = $value;
        }
    }

    #[
        OA\Property(
            description: "Whether a streamer is currently active on the station.",
            example: false
        ),
        ORM\Column,
        Attributes\AuditIgnore
    ]
    public bool $is_streamer_live = false;

    #[
        OA\Property(
            description: "Whether this station is visible as a public page and in a now-playing API response.",
            example: true
        ),
        ORM\Column,
        Serializer\Groups([EntityGroupsInterface::GROUP_GENERAL, EntityGroupsInterface::GROUP_ALL])
    ]
    public bool $enable_public_page = true;

    #[
        OA\Property(
            description: "Whether this station has a public 'on-demand' streaming and download page.",
            example: true
        ),
        ORM\Column,
        Serializer\Groups([EntityGroupsInterface::GROUP_GENERAL, EntityGroupsInterface::GROUP_ALL])
    ]
    public bool $enable_on_demand = false;

    #[
        OA\Property(
            description: "Whether the 'on-demand' page offers download capability.",
            example: true
        ),
        ORM\Column,
        Serializer\Groups([EntityGroupsInterface::GROUP_GENERAL, EntityGroupsInterface::GROUP_ALL])
    ]
    public bool $enable_on_demand_download = true;

    #[
        OA\Property(
            description: "Whether HLS streaming is enabled.",
            example: true
        ),
        ORM\Column,
        Serializer\Groups([EntityGroupsInterface::GROUP_GENERAL, EntityGroupsInterface::GROUP_ALL])
    ]
    public bool $enable_hls = false;

    #[
        ORM\Column,
        Attributes\AuditIgnore
    ]
    public bool $needs_restart = false {
        set => ($this->hasLocalServices() && $this->has_started) ? $value : false;
    }

    #[
        ORM\Column,
        Attributes\AuditIgnore
    ]
    public bool $has_started = false;

    #[
        OA\Property(
            description: "The number of 'last played' history items to show for a station in API responses.",
            example: 5
        ),
        ORM\Column(type: 'smallint'),
        Serializer\Groups([EntityGroupsInterface::GROUP_GENERAL, EntityGroupsInterface::GROUP_ALL])
    ]
    public int $api_history_items = 5;

    #[
        OA\Property(
            description: "The time zone that station operations should take place in.",
            example: "UTC"
        ),
        ORM\Column(length: 100, nullable: false),
        Serializer\Groups([EntityGroupsInterface::GROUP_GENERAL, EntityGroupsInterface::GROUP_ALL])
    ]
    public string $timezone = 'UTC';

    public function getTimezoneObject(): DateTimeZone
    {
        return new DateTimeZone($this->timezone);
    }

    #[
        OA\Property(
            description: "The maximum bitrate at which a station may broadcast, in Kbps. 0 for unlimited",
            example: 128
        ),
        ORM\Column(type: 'smallint', nullable: false, options: ['default' => 0]),
        Serializer\Groups([EntityGroupsInterface::GROUP_ADMIN, EntityGroupsInterface::GROUP_ALL])
    ]
    public int $max_bitrate = 0 {
        set {
            if ($this->max_bitrate !== $value) {
                $this->needs_restart = true;
            }
            $this->max_bitrate = $value;
        }
    }

    #[
        OA\Property(
            description: "The maximum number of mount points the station can have, 0 for unlimited",
            example: 3
        ),
        ORM\Column(type: 'smallint', nullable: false, options: ['default' => 0]),
        Serializer\Groups([EntityGroupsInterface::GROUP_ADMIN, EntityGroupsInterface::GROUP_ALL])
    ]
    public int $max_mounts = 0 {
        set {
            if ($this->max_mounts !== $value) {
                $this->needs_restart = true;
            }
            $this->max_mounts = $value;
        }
    }

    #[
        OA\Property(
            description: "The maximum number of HLS streams the station can have, 0 for unlimited",
            example: 3
        ),
        ORM\Column(type: 'smallint', nullable: false, options: ['default' => 0]),
        Serializer\Groups([EntityGroupsInterface::GROUP_ADMIN, EntityGroupsInterface::GROUP_ALL])
    ]
    public int $max_hls_streams = 0 {
        set {
            if ($this->max_hls_streams !== $value) {
                $this->needs_restart = true;
            }
            $this->max_hls_streams = $value;
        }
    }

    /**
     * @var ConfigData|null
     */
    #[ORM\Column(name: 'branding_config', type: 'json', nullable: true)]
    private ?array $branding_config_raw = null;

    #[
        OA\Property(
            description: "An array containing station-specific branding configuration",
            type: "object"
        ),
        Serializer\Groups([EntityGroupsInterface::GROUP_GENERAL, EntityGroupsInterface::GROUP_ALL])
    ]
    public StationBrandingConfiguration $branding_config {
        get => new StationBrandingConfiguration((array)$this->branding_config_raw);
        set (StationBrandingConfiguration|array|null $value) {
            $this->branding_config_raw = StationBrandingConfiguration::merge(
                $this->branding_config_raw,
                $value
            );
        }
    }

    /** @var Collection<int, SongHistory> */
    #[
        ORM\OneToMany(targetEntity: SongHistory::class, mappedBy: 'station'),
        ORM\OrderBy(['timestamp_start' => 'desc'])
    ]
    public private(set) Collection $history;

    #[
        ORM\ManyToOne,
        ORM\JoinColumn(
            name: 'media_storage_location_id',
            referencedColumnName: 'id',
            nullable: false,
            onDelete: 'CASCADE'
        ),
        DeepNormalize(true),
        Serializer\MaxDepth(1),
        Serializer\Groups([EntityGroupsInterface::GROUP_ADMIN, EntityGroupsInterface::GROUP_ALL])
    ]
    public StorageLocation $media_storage_location {
        // @phpstan-ignore propertySetHook.noAssign
        set(StorageLocation|null $value) {
            if (null === $value) {
                return;
            }

            if (StorageLocationTypes::StationMedia !== $value->type) {
                throw new RuntimeException('Invalid storage location.');
            }

            $this->media_storage_location = $value;
        }
    }

    #[
        ORM\ManyToOne,
        ORM\JoinColumn(
            name: 'recordings_storage_location_id',
            referencedColumnName: 'id',
            nullable: false,
            onDelete: 'CASCADE'
        ),
        DeepNormalize(true),
        Serializer\MaxDepth(1),
        Serializer\Groups([EntityGroupsInterface::GROUP_ADMIN, EntityGroupsInterface::GROUP_ALL])
    ]
    public StorageLocation $recordings_storage_location {
        // @phpstan-ignore propertySetHook.noAssign
        set(StorageLocation|null $value) {
            if (null === $value) {
                return;
            }

            if (StorageLocationTypes::StationRecordings !== $value->type) {
                throw new RuntimeException('Invalid storage location.');
            }

            $this->recordings_storage_location = $value;
        }
    }

    #[
        ORM\ManyToOne,
        ORM\JoinColumn(
            name: 'podcasts_storage_location_id',
            referencedColumnName: 'id',
            nullable: false,
            onDelete: 'CASCADE'
        ),
        DeepNormalize(true),
        Serializer\MaxDepth(1),
        Serializer\Groups([EntityGroupsInterface::GROUP_ADMIN, EntityGroupsInterface::GROUP_ALL])
    ]
    public StorageLocation $podcasts_storage_location {
        // @phpstan-ignore propertySetHook.noAssign
        set(StorageLocation|null $value) {
            if (null === $value) {
                return;
            }

            if (StorageLocationTypes::StationPodcasts !== $value->type) {
                throw new RuntimeException('Invalid storage location.');
            }

            $this->podcasts_storage_location = $value;
        }
    }

    /** @var Collection<int, StationStreamer> */
    #[ORM\OneToMany(targetEntity: StationStreamer::class, mappedBy: 'station')]
    public private(set) Collection $streamers;

    #[
        ORM\ManyToOne,
        ORM\JoinColumn(name: 'current_streamer_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL'),
        Attributes\AuditIgnore
    ]
    public ?StationStreamer $current_streamer = null {
        // @phpstan-ignore propertySetHook.noAssign
        set {
            if (null !== $this->current_streamer || null !== $value) {
                $this->current_streamer = $value;
            }
        }
    }

    #[ORM\Column(length: 255, nullable: true)]
    public ?string $fallback_path = null {
        set {
            if ($this->fallback_path !== $value) {
                $this->needs_restart = true;
            }
            $this->fallback_path = $value;
        }
    }

    /** @var Collection<int, RolePermission> */
    #[ORM\OneToMany(targetEntity: RolePermission::class, mappedBy: 'station')]
    public private(set) Collection $permissions;

    /** @var Collection<int, StationPlaylist> */
    #[
        ORM\OneToMany(targetEntity: StationPlaylist::class, mappedBy: 'station'),
        ORM\OrderBy(['type' => 'ASC', 'weight' => 'DESC'])
    ]
    public private(set) Collection $playlists;

    /** @var Collection<int, StationMount> */
    #[ORM\OneToMany(targetEntity: StationMount::class, mappedBy: 'station')]
    public private(set) Collection $mounts;

    /** @var Collection<int, StationRemote> */
    #[ORM\OneToMany(targetEntity: StationRemote::class, mappedBy: 'station')]
    public private(set) Collection $remotes;

    /** @var Collection<int, StationHlsStream> */
    #[ORM\OneToMany(targetEntity: StationHlsStream::class, mappedBy: 'station')]
    public private(set) Collection $hls_streams;

    /** @var Collection<int, StationWebhook> */
    #[ORM\OneToMany(
        targetEntity: StationWebhook::class,
        mappedBy: 'station',
        cascade: ['persist'],
        fetch: 'EXTRA_LAZY'
    )]
    public private(set) Collection $webhooks;

    /** @var Collection<int, StationStreamerBroadcast> */
    #[ORM\OneToMany(targetEntity: StationStreamerBroadcast::class, mappedBy: 'station')]
    public private(set) Collection $streamer_broadcasts;

    /** @var Collection<int, SftpUser> */
    #[ORM\OneToMany(targetEntity: SftpUser::class, mappedBy: 'station')]
    public private(set) Collection $sftp_users;

    /** @var Collection<int, StationRequest> */
    #[ORM\OneToMany(targetEntity: StationRequest::class, mappedBy: 'station')]
    public private(set) Collection $requests;

    #[
        ORM\ManyToOne,
        ORM\JoinColumn(name: 'current_song_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL'),
        Attributes\AuditIgnore
    ]
    public ?SongHistory $current_song = null;

    public function __construct()
    {
        $this->generateAdapterApiKey();

        $this->frontend_config_raw = new StationFrontendConfiguration([])->getData();
        $this->backend_config_raw = new StationBackendConfiguration([])->getData();
        $this->branding_config_raw = new StationBrandingConfiguration([])->getData();

        $this->frontend_type = FrontendAdapters::default();
        $this->backend_type = BackendAdapters::default();

        $this->history = new ArrayCollection();
        $this->permissions = new ArrayCollection();
        $this->playlists = new ArrayCollection();
        $this->mounts = new ArrayCollection();
        $this->remotes = new ArrayCollection();
        $this->hls_streams = new ArrayCollection();
        $this->webhooks = new ArrayCollection();
        $this->streamers = new ArrayCollection();
        $this->streamer_broadcasts = new ArrayCollection();
        $this->sftp_users = new ArrayCollection();
        $this->requests = new ArrayCollection();
    }

    public function supportsAutoDjQueue(): bool
    {
        return $this->is_enabled
            && !$this->backend_config->use_manual_autodj
            && BackendAdapters::None !== $this->backend_type;
    }

    public function hasLocalServices(): bool
    {
        return $this->is_enabled &&
            ($this->backend_type->isEnabled() || $this->frontend_type->isEnabled());
    }

    public function ensureDirectoriesExist(): void
    {
        if (!isset($this->radio_base_dir)) {
            $this->radio_base_dir = $this->short_name;
        }

        // Flysystem adapters will automatically create the main directory.
        File::mkdirIfNotExists($this->radio_base_dir);
        File::mkdirIfNotExists($this->getRadioPlaylistsDir());
        File::mkdirIfNotExists($this->getRadioConfigDir());
        File::mkdirIfNotExists($this->getRadioTempDir());
        File::mkdirIfNotExists($this->getRadioHlsDir());

        if (!isset($this->media_storage_location)) {
            $this->createMediaStorageLocation();
        }

        if (!isset($this->recordings_storage_location)) {
            $this->createRecordingsStorageLocation();
        }

        if (!isset($this->podcasts_storage_location)) {
            $this->createPodcastsStorageLocation();
        }
    }

    public function createMediaStorageLocation(): void
    {
        $storageLocation = new StorageLocation(
            StorageLocationTypes::StationMedia,
            StorageLocationAdapters::Local
        );

        $mediaPath = $this->radio_base_dir . '/media';
        File::mkdirIfNotExists($mediaPath);
        $storageLocation->path = $mediaPath;

        $this->media_storage_location = $storageLocation;
    }

    public function createRecordingsStorageLocation(): void
    {
        $storageLocation = new StorageLocation(
            StorageLocationTypes::StationRecordings,
            StorageLocationAdapters::Local
        );

        $recordingsPath = $this->radio_base_dir . '/recordings';
        File::mkdirIfNotExists($recordingsPath);
        $storageLocation->path = $recordingsPath;

        $this->recordings_storage_location = $storageLocation;
    }

    public function createPodcastsStorageLocation(): void
    {
        $storageLocation = new StorageLocation(
            StorageLocationTypes::StationPodcasts,
            StorageLocationAdapters::Local
        );

        $podcastsPath = $this->radio_base_dir . '/podcasts';
        File::mkdirIfNotExists($podcastsPath);
        $storageLocation->path = $podcastsPath;

        $this->podcasts_storage_location = $storageLocation;
    }

    public function getRadioPlaylistsDir(): string
    {
        return $this->radio_base_dir . '/' . self::PLAYLISTS_DIR;
    }

    public function getRadioConfigDir(): string
    {
        return $this->radio_base_dir . '/' . self::CONFIG_DIR;
    }

    public function getRadioTempDir(): string
    {
        return $this->radio_base_dir . '/' . self::TEMP_DIR;
    }

    public function getRadioHlsDir(): string
    {
        return $this->radio_base_dir . '/' . self::HLS_DIR;
    }

    public function getStorageLocation(StorageLocationTypes $type): StorageLocation
    {
        return match ($type) {
            StorageLocationTypes::StationMedia => $this->media_storage_location,
            StorageLocationTypes::StationRecordings => $this->recordings_storage_location,
            StorageLocationTypes::StationPodcasts => $this->podcasts_storage_location,
            default => throw new InvalidArgumentException('Invalid storage location.')
        };
    }

    /** @return StorageLocation[] */
    public function getAllStorageLocations(): array
    {
        return [
            $this->media_storage_location,
            $this->recordings_storage_location,
            $this->podcasts_storage_location,
        ];
    }

    /**
     * @return array<string, StorageLocationTypes>
     */
    public static function getStorageLocationTypes(): array
    {
        return [
            'media_storage_location' => StorageLocationTypes::StationMedia,
            'recordings_storage_location' => StorageLocationTypes::StationRecordings,
            'podcasts_storage_location' => StorageLocationTypes::StationPodcasts,
        ];
    }

    public function __toString(): string
    {
        $name = $this->name;
        if (!empty($name)) {
            return $name;
        }

        return isset($this->id) ? 'Station #' . $this->id : 'New Station';
    }

    public function __clone()
    {
        $this->name = '';
        $this->short_name = '';
        $this->adapter_api_key = null;
        $this->current_streamer = null;
        $this->is_streamer_live = false;
        $this->needs_restart = false;
        $this->has_started = false;
        $this->current_song = null;

        // Clear ports
        $feConfig = $this->frontend_config;
        $feConfig->port = null;

        $this->frontend_config = $feConfig;

        $beConfig = $this->backend_config;
        $beConfig->dj_port = null;
        $beConfig->telnet_port = null;

        $this->backend_config = $beConfig;

        // Clear collections
        $this->history = new ArrayCollection();
        $this->permissions = new ArrayCollection();
        $this->playlists = new ArrayCollection();
        $this->mounts = new ArrayCollection();
        $this->remotes = new ArrayCollection();
        $this->hls_streams = new ArrayCollection();
        $this->webhooks = new ArrayCollection();
        $this->streamers = new ArrayCollection();
        $this->streamer_broadcasts = new ArrayCollection();
        $this->sftp_users = new ArrayCollection();
        $this->requests = new ArrayCollection();
    }

    public static function generateShortName(string $str): string
    {
        $str = File::sanitizeFileName($str);

        return (is_numeric($str))
            ? 'station_' . $str
            : $str;
    }
}
