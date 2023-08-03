<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Enums\StorageLocationAdapters;
use App\Entity\Enums\StorageLocationTypes;
use App\Entity\Interfaces\EntityGroupsInterface;
use App\Entity\Interfaces\IdentifiableEntityInterface;
use App\Environment;
use App\Normalizer\Attributes\DeepNormalize;
use App\Radio\Enums\BackendAdapters;
use App\Radio\Enums\FrontendAdapters;
use App\Utilities\File;
use App\Validator\Constraints as AppAssert;
use DateTimeZone;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use League\Flysystem\UnixVisibility\PortableVisibilityConverter;
use League\Flysystem\Visibility;
use OpenApi\Attributes as OA;
use RuntimeException;
use Stringable;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

#[
    OA\Schema(schema: "Station", type: "object"),
    ORM\Entity,
    ORM\Table(name: 'station'),
    ORM\Index(columns: ['short_name'], name: 'idx_short_name'),
    ORM\HasLifecycleCallbacks,
    Attributes\Auditable,
    AppAssert\StationPortChecker,
    AppAssert\UniqueEntity(fields: ['short_name'])
]
class Station implements Stringable, IdentifiableEntityInterface
{
    use Traits\HasAutoIncrementId;
    use Traits\TruncateStrings;

    #[
        OA\Property(description: "The full display name of the station.", example: "AzuraTest Radio"),
        ORM\Column(length: 100, nullable: false),
        Assert\NotBlank,
        Serializer\Groups([EntityGroupsInterface::GROUP_GENERAL, EntityGroupsInterface::GROUP_ALL])
    ]
    protected string $name = '';

    #[
        OA\Property(
            description: "The URL-friendly name for the station, typically auto-generated from the full station name.",
            example: "azuratest_radio"
        ),
        ORM\Column(length: 100, nullable: false),
        Assert\NotBlank,
        Serializer\Groups([EntityGroupsInterface::GROUP_GENERAL, EntityGroupsInterface::GROUP_ALL])
    ]
    protected string $short_name = '';

    #[
        OA\Property(
            description: "If set to 'false', prevents the station from broadcasting but leaves it in the database.",
            example: true
        ),
        ORM\Column,
        Serializer\Groups([EntityGroupsInterface::GROUP_ADMIN, EntityGroupsInterface::GROUP_ALL])
    ]
    protected bool $is_enabled = true;

    #[
        OA\Property(
            description: "The frontend adapter (icecast,shoutcast,remote,etc)",
            example: "icecast"
        ),
        ORM\Column(type: 'string', length: 100, enumType: FrontendAdapters::class),
        Serializer\Groups([EntityGroupsInterface::GROUP_GENERAL, EntityGroupsInterface::GROUP_ALL])
    ]
    protected FrontendAdapters $frontend_type;

    #[
        OA\Property(
            description: "An array containing station-specific frontend configuration",
            type: "array",
            items: new OA\Items()
        ),
        ORM\Column(type: 'json', nullable: true),
        Serializer\Groups([EntityGroupsInterface::GROUP_GENERAL, EntityGroupsInterface::GROUP_ALL])
    ]
    protected ?array $frontend_config = null;

    #[
        OA\Property(
            description: "The backend adapter (liquidsoap,etc)",
            example: "liquidsoap"
        ),
        ORM\Column(type: 'string', length: 100, enumType: BackendAdapters::class),
        Serializer\Groups([EntityGroupsInterface::GROUP_GENERAL, EntityGroupsInterface::GROUP_ALL])
    ]
    protected BackendAdapters $backend_type;

    #[
        OA\Property(
            description: "An array containing station-specific backend configuration",
            type: "array",
            items: new OA\Items()
        ),
        ORM\Column(type: 'json', nullable: true),
        Serializer\Groups([EntityGroupsInterface::GROUP_GENERAL, EntityGroupsInterface::GROUP_ALL])
    ]
    protected ?array $backend_config = null;

    #[
        ORM\Column(length: 150, nullable: true),
        Attributes\AuditIgnore
    ]
    protected ?string $adapter_api_key = null;

    #[
        OA\Property(example: "A sample radio station."),
        ORM\Column(type: 'text', nullable: true),
        Serializer\Groups([EntityGroupsInterface::GROUP_GENERAL, EntityGroupsInterface::GROUP_ALL])
    ]
    protected ?string $description = null;

    #[
        OA\Property(example: "https://demo.azuracast.com/"),
        ORM\Column(length: 255, nullable: true),
        Serializer\Groups([EntityGroupsInterface::GROUP_GENERAL, EntityGroupsInterface::GROUP_ALL])
    ]
    protected ?string $url = null;

    #[
        OA\Property(example: "Various"),
        ORM\Column(length: 255, nullable: true),
        Serializer\Groups([EntityGroupsInterface::GROUP_GENERAL, EntityGroupsInterface::GROUP_ALL])
    ]
    protected ?string $genre = null;

    #[
        OA\Property(example: "/var/azuracast/stations/azuratest_radio"),
        ORM\Column(length: 255, nullable: true),
        Serializer\Groups([EntityGroupsInterface::GROUP_ADMIN, EntityGroupsInterface::GROUP_ALL])
    ]
    protected ?string $radio_base_dir = null;

    #[
        OA\Property(
            description: "Whether listeners can request songs to play on this station.",
            example: true
        ),
        ORM\Column,
        Serializer\Groups([EntityGroupsInterface::GROUP_GENERAL, EntityGroupsInterface::GROUP_ALL])
    ]
    protected bool $enable_requests = false;

    #[
        OA\Property(example: 5),
        ORM\Column(nullable: true),
        Serializer\Groups([EntityGroupsInterface::GROUP_GENERAL, EntityGroupsInterface::GROUP_ALL])
    ]
    protected ?int $request_delay = 5;

    #[
        OA\Property(example: 15),
        ORM\Column(nullable: true),
        Serializer\Groups([EntityGroupsInterface::GROUP_GENERAL, EntityGroupsInterface::GROUP_ALL])
    ]
    protected ?int $request_threshold = 15;

    #[
        OA\Property(example: 0),
        ORM\Column(nullable: true, options: ['default' => 0]),
        Serializer\Groups([EntityGroupsInterface::GROUP_GENERAL, EntityGroupsInterface::GROUP_ALL])
    ]
    protected ?int $disconnect_deactivate_streamer = 0;

    #[
        OA\Property(
            description: "Whether streamers are allowed to broadcast to this station at all.",
            example: false
        ),
        ORM\Column,
        Serializer\Groups([EntityGroupsInterface::GROUP_GENERAL, EntityGroupsInterface::GROUP_ALL])
    ]
    protected bool $enable_streamers = false;

    #[
        OA\Property(
            description: "Whether a streamer is currently active on the station.",
            example: false
        ),
        ORM\Column,
        Attributes\AuditIgnore
    ]
    protected bool $is_streamer_live = false;

    #[
        OA\Property(
            description: "Whether this station is visible as a public page and in a now-playing API response.",
            example: true
        ),
        ORM\Column,
        Serializer\Groups([EntityGroupsInterface::GROUP_GENERAL, EntityGroupsInterface::GROUP_ALL])
    ]
    protected bool $enable_public_page = true;

    #[
        OA\Property(
            description: "Whether this station has a public 'on-demand' streaming and download page.",
            example: true
        ),
        ORM\Column,
        Serializer\Groups([EntityGroupsInterface::GROUP_GENERAL, EntityGroupsInterface::GROUP_ALL])
    ]
    protected bool $enable_on_demand = false;

    #[
        OA\Property(
            description: "Whether the 'on-demand' page offers download capability.",
            example: true
        ),
        ORM\Column,
        Serializer\Groups([EntityGroupsInterface::GROUP_GENERAL, EntityGroupsInterface::GROUP_ALL])
    ]
    protected bool $enable_on_demand_download = true;

    #[
        OA\Property(
            description: "Whether HLS streaming is enabled.",
            example: true
        ),
        ORM\Column,
        Serializer\Groups([EntityGroupsInterface::GROUP_GENERAL, EntityGroupsInterface::GROUP_ALL])
    ]
    protected bool $enable_hls = false;

    #[
        ORM\Column,
        Attributes\AuditIgnore
    ]
    protected bool $needs_restart = false;

    #[
        ORM\Column,
        Attributes\AuditIgnore
    ]
    protected bool $has_started = false;

    #[
        OA\Property(
            description: "The number of 'last played' history items to show for a station in API responses.",
            example: 5
        ),
        ORM\Column(type: 'smallint'),
        Serializer\Groups([EntityGroupsInterface::GROUP_GENERAL, EntityGroupsInterface::GROUP_ALL])
    ]
    protected int $api_history_items = 5;

    #[
        OA\Property(
            description: "The time zone that station operations should take place in.",
            example: "UTC"
        ),
        ORM\Column(length: 100, nullable: true),
        Serializer\Groups([EntityGroupsInterface::GROUP_GENERAL, EntityGroupsInterface::GROUP_ALL])
    ]
    protected ?string $timezone = 'UTC';

    #[
        OA\Property(
            description: "An array containing station-specific branding configuration",
            type: "array",
            items: new OA\Items()
        ),
        ORM\Column(type: 'json', nullable: true),
        Serializer\Groups([EntityGroupsInterface::GROUP_GENERAL, EntityGroupsInterface::GROUP_ALL])
    ]
    protected ?array $branding_config = null;

    /** @var Collection<int, SongHistory> */
    #[
        ORM\OneToMany(mappedBy: 'station', targetEntity: SongHistory::class),
        ORM\OrderBy(['timestamp_start' => 'desc'])
    ]
    protected Collection $history;

    #[
        ORM\ManyToOne,
        ORM\JoinColumn(
            name: 'media_storage_location_id',
            referencedColumnName: 'id',
            nullable: true,
            onDelete: 'SET NULL'
        ),
        DeepNormalize(true),
        Serializer\MaxDepth(1),
        Serializer\Groups([EntityGroupsInterface::GROUP_ADMIN, EntityGroupsInterface::GROUP_ALL])
    ]
    protected ?StorageLocation $media_storage_location = null;

    #[
        ORM\ManyToOne,
        ORM\JoinColumn(
            name: 'recordings_storage_location_id',
            referencedColumnName: 'id',
            nullable: true,
            onDelete: 'SET NULL'
        ),
        DeepNormalize(true),
        Serializer\MaxDepth(1),
        Serializer\Groups([EntityGroupsInterface::GROUP_ADMIN, EntityGroupsInterface::GROUP_ALL])
    ]
    protected ?StorageLocation $recordings_storage_location = null;

    #[
        ORM\ManyToOne,
        ORM\JoinColumn(
            name: 'podcasts_storage_location_id',
            referencedColumnName: 'id',
            nullable: true,
            onDelete: 'SET NULL'
        ),
        DeepNormalize(true),
        Serializer\MaxDepth(1),
        Serializer\Groups([EntityGroupsInterface::GROUP_ADMIN, EntityGroupsInterface::GROUP_ALL])
    ]
    protected ?StorageLocation $podcasts_storage_location = null;

    /** @var Collection<int, StationStreamer> */
    #[ORM\OneToMany(mappedBy: 'station', targetEntity: StationStreamer::class)]
    protected Collection $streamers;

    #[
        ORM\ManyToOne,
        ORM\JoinColumn(name: 'current_streamer_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL'),
        Attributes\AuditIgnore
    ]
    protected ?StationStreamer $current_streamer = null;

    #[
        ORM\Column(nullable: true, insertable: false, updatable: false),
        Attributes\AuditIgnore
    ]
    private ?int $current_streamer_id = null;

    #[ORM\Column(length: 255, nullable: true)]
    protected ?string $fallback_path = null;

    /** @var Collection<int, RolePermission> */
    #[ORM\OneToMany(mappedBy: 'station', targetEntity: RolePermission::class)]
    protected Collection $permissions;

    /** @var Collection<int, StationPlaylist> */
    #[
        ORM\OneToMany(mappedBy: 'station', targetEntity: StationPlaylist::class),
        ORM\OrderBy(['type' => 'ASC', 'weight' => 'DESC'])
    ]
    protected Collection $playlists;

    /** @var Collection<int, StationMount> */
    #[ORM\OneToMany(mappedBy: 'station', targetEntity: StationMount::class)]
    protected Collection $mounts;

    /** @var Collection<int, StationRemote> */
    #[ORM\OneToMany(mappedBy: 'station', targetEntity: StationRemote::class)]
    protected Collection $remotes;

    /** @var Collection<int, StationHlsStream> */
    #[ORM\OneToMany(mappedBy: 'station', targetEntity: StationHlsStream::class)]
    protected Collection $hls_streams;

    /** @var Collection<int, StationWebhook> */
    #[ORM\OneToMany(
        mappedBy: 'station',
        targetEntity: StationWebhook::class,
        cascade: ['persist'],
        fetch: 'EXTRA_LAZY'
    )]
    protected Collection $webhooks;

    /** @var Collection<int, SftpUser> */
    #[ORM\OneToMany(mappedBy: 'station', targetEntity: SftpUser::class)]
    protected Collection $sftp_users;

    #[
        ORM\ManyToOne,
        ORM\JoinColumn(name: 'current_song_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL'),
        Attributes\AuditIgnore
    ]
    protected ?SongHistory $current_song = null;

    public function __construct()
    {
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
        $this->sftp_users = new ArrayCollection();
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $this->truncateString($name, 100);

        if (empty($this->short_name) && !empty($name)) {
            $this->setShortName(self::generateShortName($name));
        }
    }

    public function getShortName(): string
    {
        return (!empty($this->short_name))
            ? $this->short_name
            : self::generateShortName($this->name);
    }

    public function setShortName(string $shortName): void
    {
        $shortName = trim($shortName);
        if (empty($shortName)) {
            $shortName = $this->name;
        }

        $shortName = self::generateShortName($shortName);

        $shortName = $this->truncateString($shortName, 100);
        if ($this->short_name !== $shortName) {
            $this->setNeedsRestart(true);
        }
        $this->short_name = $shortName;
    }

    public function setIsEnabled(bool $isEnabled): void
    {
        $this->is_enabled = $isEnabled;
    }

    public function getFrontendType(): FrontendAdapters
    {
        return $this->frontend_type;
    }

    public function setFrontendType(FrontendAdapters $frontendType): void
    {
        $this->frontend_type = $frontendType;
    }

    public function getFrontendConfig(): StationFrontendConfiguration
    {
        return new StationFrontendConfiguration((array)$this->frontend_config);
    }

    public function setFrontendConfig(
        StationFrontendConfiguration|array $frontendConfig,
        bool $forceOverwrite = false
    ): void {
        if (is_array($frontendConfig)) {
            $frontendConfig = new StationFrontendConfiguration(
                $forceOverwrite ? $frontendConfig : array_merge((array)$this->frontend_config, $frontendConfig)
            );
        }

        $config = $frontendConfig->toArray();
        if ($this->frontend_config !== $config) {
            $this->setNeedsRestart(true);
        }
        $this->frontend_config = $config;
    }

    public function getBackendType(): BackendAdapters
    {
        return $this->backend_type;
    }

    public function setBackendType(BackendAdapters $backendType): void
    {
        $this->backend_type = $backendType;
    }

    /**
     * Whether the station uses AzuraCast to directly manage the AutoDJ or lets the backend handle it.
     */
    public function useManualAutoDJ(): bool
    {
        return $this->getBackendConfig()->useManualAutoDj();
    }

    public function supportsAutoDjQueue(): bool
    {
        return $this->getIsEnabled()
            && !$this->useManualAutoDJ()
            && BackendAdapters::None !== $this->getBackendType();
    }

    public function getBackendConfig(): StationBackendConfiguration
    {
        return new StationBackendConfiguration((array)$this->backend_config);
    }

    public function hasLocalServices(): bool
    {
        return $this->getIsEnabled() &&
            ($this->getBackendType()->isEnabled() || $this->getFrontendType()->isEnabled());
    }

    public function setBackendConfig(
        StationBackendConfiguration|array $backendConfig,
        bool $forceOverwrite = false
    ): void {
        if (is_array($backendConfig)) {
            $backendConfig = new StationBackendConfiguration(
                $forceOverwrite ? $backendConfig : array_merge((array)$this->backend_config, $backendConfig)
            );
        }

        $config = $backendConfig->toArray();

        if ($this->backend_config !== $config) {
            $this->setNeedsRestart(true);
        }

        $this->backend_config = $config;
    }

    public function getAdapterApiKey(): ?string
    {
        return $this->adapter_api_key;
    }

    /**
     * Generate a random new adapter API key.
     */
    public function generateAdapterApiKey(): void
    {
        $this->adapter_api_key = bin2hex(random_bytes(50));
    }

    /**
     * Authenticate the supplied adapter API key.
     *
     * @param string $apiKey
     */
    public function validateAdapterApiKey(string $apiKey): bool
    {
        return hash_equals($apiKey, $this->adapter_api_key ?? '');
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description = null): void
    {
        $this->description = $description;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url = null): void
    {
        $this->url = $this->truncateNullableString($url);
    }

    public function getGenre(): ?string
    {
        return $this->genre;
    }

    public function setGenre(?string $genre): void
    {
        $this->genre = $this->truncateNullableString($genre);
    }

    public function getRadioBaseDir(): string
    {
        if (null === $this->radio_base_dir) {
            $this->setRadioBaseDir();
        }

        return (string)$this->radio_base_dir;
    }

    public function setRadioBaseDir(?string $newDir = null): void
    {
        $newDir = $this->truncateNullableString(trim($newDir ?? ''));

        if (empty($newDir)) {
            $newDir = $this->getShortName();
        }

        if (Path::isRelative($newDir)) {
            $newDir = Path::makeAbsolute(
                $newDir,
                Environment::getInstance()->getStationDirectory()
            );
        }

        $this->radio_base_dir = $newDir;
    }

    public function ensureDirectoriesExist(): void
    {
        // Flysystem adapters will automatically create the main directory.
        $this->ensureDirectoryExists($this->getRadioBaseDir());
        $this->ensureDirectoryExists($this->getRadioPlaylistsDir());
        $this->ensureDirectoryExists($this->getRadioConfigDir());
        $this->ensureDirectoryExists($this->getRadioTempDir());
        $this->ensureDirectoryExists($this->getRadioHlsDir());

        if (null === $this->media_storage_location) {
            $storageLocation = new StorageLocation(
                StorageLocationTypes::StationMedia,
                StorageLocationAdapters::Local
            );

            $mediaPath = $this->getRadioBaseDir() . '/media';
            $this->ensureDirectoryExists($mediaPath);
            $storageLocation->setPath($mediaPath);

            $this->media_storage_location = $storageLocation;
        }

        if (null === $this->recordings_storage_location) {
            $storageLocation = new StorageLocation(
                StorageLocationTypes::StationRecordings,
                StorageLocationAdapters::Local
            );

            $recordingsPath = $this->getRadioBaseDir() . '/recordings';
            $this->ensureDirectoryExists($recordingsPath);
            $storageLocation->setPath($recordingsPath);

            $this->recordings_storage_location = $storageLocation;
        }

        if (null === $this->podcasts_storage_location) {
            $storageLocation = new StorageLocation(
                StorageLocationTypes::StationPodcasts,
                StorageLocationAdapters::Local
            );

            $podcastsPath = $this->getRadioBaseDir() . '/podcasts';
            $this->ensureDirectoryExists($podcastsPath);
            $storageLocation->setPath($podcastsPath);

            $this->podcasts_storage_location = $storageLocation;
        }
    }

    protected function ensureDirectoryExists(string $dirname): void
    {
        if (is_dir($dirname)) {
            return;
        }

        $visibility = (new PortableVisibilityConverter(
            defaultForDirectories: Visibility::PUBLIC
        ))->defaultForDirectories();

        (new Filesystem())->mkdir($dirname, $visibility);
    }

    public function getRadioPlaylistsDir(): string
    {
        return $this->radio_base_dir . '/playlists';
    }

    public function getRadioConfigDir(): string
    {
        return $this->radio_base_dir . '/config';
    }

    public function getRadioTempDir(): string
    {
        return $this->radio_base_dir . '/temp';
    }

    public function getRadioHlsDir(): string
    {
        return $this->radio_base_dir . '/hls';
    }

    public function getEnableRequests(): bool
    {
        return $this->enable_requests;
    }

    public function setEnableRequests(bool $enableRequests): void
    {
        $this->enable_requests = $enableRequests;
    }

    public function getRequestDelay(): ?int
    {
        return $this->request_delay;
    }

    public function setRequestDelay(int $requestDelay = null): void
    {
        $this->request_delay = $requestDelay;
    }

    public function getRequestThreshold(): ?int
    {
        return $this->request_threshold;
    }

    public function setRequestThreshold(int $requestThreshold = null): void
    {
        $this->request_threshold = $requestThreshold;
    }

    public function getDisconnectDeactivateStreamer(): ?int
    {
        return $this->disconnect_deactivate_streamer;
    }

    public function setDisconnectDeactivateStreamer(?int $disconnectDeactivateStreamer): void
    {
        $this->disconnect_deactivate_streamer = $disconnectDeactivateStreamer;
    }

    public function getEnableStreamers(): bool
    {
        return $this->enable_streamers;
    }

    public function setEnableStreamers(bool $enableStreamers): void
    {
        if ($this->enable_streamers !== $enableStreamers) {
            $this->setNeedsRestart(true);
        }

        $this->enable_streamers = $enableStreamers;
    }

    public function getIsStreamerLive(): bool
    {
        return $this->is_streamer_live;
    }

    public function setIsStreamerLive(bool $isStreamerLive): void
    {
        $this->is_streamer_live = $isStreamerLive;
    }

    public function getEnablePublicPage(): bool
    {
        return $this->enable_public_page && $this->getIsEnabled();
    }

    public function setEnablePublicPage(bool $enablePublicPage): void
    {
        $this->enable_public_page = $enablePublicPage;
    }

    public function getEnableOnDemand(): bool
    {
        return $this->enable_on_demand;
    }

    public function setEnableOnDemand(bool $enableOnDemand): void
    {
        $this->enable_on_demand = $enableOnDemand;
    }

    public function getEnableOnDemandDownload(): bool
    {
        return $this->enable_on_demand_download;
    }

    public function setEnableOnDemandDownload(bool $enableOnDemandDownload): void
    {
        $this->enable_on_demand_download = $enableOnDemandDownload;
    }

    public function getEnableHls(): bool
    {
        return $this->enable_hls;
    }

    public function setEnableHls(bool $enableHls): void
    {
        $this->enable_hls = $enableHls;
    }

    public function getIsEnabled(): bool
    {
        return $this->is_enabled;
    }

    public function getNeedsRestart(): bool
    {
        return $this->needs_restart;
    }

    public function setNeedsRestart(bool $needsRestart): void
    {
        $this->needs_restart = $needsRestart;
    }

    public function getHasStarted(): bool
    {
        return $this->has_started;
    }

    public function setHasStarted(bool $hasStarted): void
    {
        $this->has_started = $hasStarted;
    }

    public function getApiHistoryItems(): int
    {
        return $this->api_history_items ?? 5;
    }

    public function setApiHistoryItems(int $apiHistoryItems): void
    {
        $this->api_history_items = $apiHistoryItems;
    }

    public function getTimezone(): string
    {
        if (!empty($this->timezone)) {
            return $this->timezone;
        }

        return 'UTC';
    }

    public function getTimezoneObject(): DateTimeZone
    {
        return new DateTimeZone($this->getTimezone());
    }

    public function setTimezone(?string $timezone): void
    {
        $this->timezone = $timezone;
    }

    public function getBrandingConfig(): StationBrandingConfiguration
    {
        return new StationBrandingConfiguration((array)$this->branding_config);
    }

    public function setBrandingConfig(
        StationBrandingConfiguration|array $brandingConfig,
        bool $forceOverwrite = false
    ): void {
        if (is_array($brandingConfig)) {
            $brandingConfig = new StationBrandingConfiguration(
                $forceOverwrite ? $brandingConfig : array_merge((array)$this->branding_config, $brandingConfig)
            );
        }

        $this->branding_config = $brandingConfig->toArray();
    }

    /**
     * @return Collection<int, SongHistory>
     */
    public function getHistory(): Collection
    {
        return $this->history;
    }

    /**
     * @return Collection<int, StationStreamer>
     */
    public function getStreamers(): Collection
    {
        return $this->streamers;
    }

    public function getCurrentStreamer(): ?StationStreamer
    {
        return $this->current_streamer;
    }

    public function setCurrentStreamer(?StationStreamer $currentStreamer): void
    {
        if (null !== $this->current_streamer || null !== $currentStreamer) {
            $this->current_streamer = $currentStreamer;
        }
    }

    public function getMediaStorageLocation(): StorageLocation
    {
        if (null === $this->media_storage_location) {
            throw new RuntimeException('Media storage location not initialized.');
        }
        return $this->media_storage_location;
    }

    public function setMediaStorageLocation(?StorageLocation $storageLocation = null): void
    {
        if (null !== $storageLocation && StorageLocationTypes::StationMedia !== $storageLocation->getType()) {
            throw new RuntimeException('Invalid storage location.');
        }

        $this->media_storage_location = $storageLocation;
    }

    public function getRecordingsStorageLocation(): StorageLocation
    {
        if (null === $this->recordings_storage_location) {
            throw new RuntimeException('Recordings storage location not initialized.');
        }
        return $this->recordings_storage_location;
    }

    public function setRecordingsStorageLocation(?StorageLocation $storageLocation = null): void
    {
        if (null !== $storageLocation && StorageLocationTypes::StationRecordings !== $storageLocation->getType()) {
            throw new RuntimeException('Invalid storage location.');
        }

        $this->recordings_storage_location = $storageLocation;
    }

    public function getPodcastsStorageLocation(): StorageLocation
    {
        if (null === $this->podcasts_storage_location) {
            throw new RuntimeException('Podcasts storage location not initialized.');
        }
        return $this->podcasts_storage_location;
    }

    public function setPodcastsStorageLocation(?StorageLocation $storageLocation = null): void
    {
        if (null !== $storageLocation && StorageLocationTypes::StationPodcasts !== $storageLocation->getType()) {
            throw new RuntimeException('Invalid storage location.');
        }

        $this->podcasts_storage_location = $storageLocation;
    }

    public function getStorageLocation(StorageLocationTypes $type): StorageLocation
    {
        return match ($type) {
            StorageLocationTypes::StationMedia => $this->getMediaStorageLocation(),
            StorageLocationTypes::StationRecordings => $this->getRecordingsStorageLocation(),
            StorageLocationTypes::StationPodcasts => $this->getPodcastsStorageLocation(),
            default => throw new InvalidArgumentException('Invalid storage location.')
        };
    }

    /** @return StorageLocation[] */
    public function getAllStorageLocations(): array
    {
        return [
            $this->getMediaStorageLocation(),
            $this->getRecordingsStorageLocation(),
            $this->getPodcastsStorageLocation(),
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

    public function getFallbackPath(): ?string
    {
        return $this->fallback_path;
    }

    public function setFallbackPath(?string $fallbackPath): void
    {
        if ($this->fallback_path !== $fallbackPath) {
            $this->setNeedsRestart(true);
        }
        $this->fallback_path = $fallbackPath;
    }

    /**
     * @return Collection<int, RolePermission>
     */
    public function getPermissions(): Collection
    {
        return $this->permissions;
    }

    /**
     * @return Collection<int, StationMedia>
     */
    public function getMedia(): Collection
    {
        return $this->getMediaStorageLocation()->getMedia();
    }

    /**
     * @return Collection<int, StationPlaylist>
     */
    public function getPlaylists(): Collection
    {
        return $this->playlists;
    }

    /**
     * @return Collection<int, StationMount>
     */
    public function getMounts(): Collection
    {
        return $this->mounts;
    }

    /**
     * @return Collection<int, StationRemote>
     */
    public function getRemotes(): Collection
    {
        return $this->remotes;
    }

    /**
     * @return Collection<int, StationHlsStream>
     */
    public function getHlsStreams(): Collection
    {
        return $this->hls_streams;
    }

    /**
     * @return Collection<int, StationWebhook>
     */
    public function getWebhooks(): Collection
    {
        return $this->webhooks;
    }

    /**
     * @return Collection<int, SftpUser>
     */
    public function getSftpUsers(): Collection
    {
        return $this->sftp_users;
    }

    public function getCurrentSong(): ?SongHistory
    {
        return $this->current_song;
    }

    public function setCurrentSong(?SongHistory $currentSong): void
    {
        $this->current_song = $currentSong;
    }

    public function __toString(): string
    {
        $name = $this->getName();
        if (null !== $name) {
            return $name;
        }

        $id = $this->getId();
        return (null !== $id) ? 'Station #' . $id : 'New Station';
    }

    public function __clone()
    {
        $this->id = null;
        $this->short_name = '';
        $this->radio_base_dir = null;
        $this->adapter_api_key = null;
        $this->current_streamer = null;
        $this->current_streamer_id = null;
        $this->is_streamer_live = false;
        $this->needs_restart = false;
        $this->has_started = false;
        $this->current_song = null;

        $this->media_storage_location = null;
        $this->recordings_storage_location = null;
        $this->podcasts_storage_location = null;

        // Clear ports
        $feConfig = $this->getFrontendConfig();
        $feConfig->setPort(null);
        $this->setFrontendConfig($feConfig);

        $beConfig = $this->getBackendConfig();
        $beConfig->setDjPort(null);
        $beConfig->setTelnetPort(null);
        $this->setBackendConfig($beConfig);
    }

    public static function generateShortName(string $str): string
    {
        $str = File::sanitizeFileName($str);

        return (is_numeric($str))
            ? 'station_' . $str
            : $str;
    }
}
