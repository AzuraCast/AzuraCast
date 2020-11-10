<?php

namespace App\Entity;

use App\Annotations\AuditLog;
use App\File;
use App\Normalizer\Annotation\DeepNormalize;
use App\Radio\Adapters;
use App\Settings;
use App\Validator\Constraints as AppAssert;
use DateTimeZone;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use League\Flysystem\Adapter\Local;
use League\Flysystem\AdapterInterface;
use OpenApi\Annotations as OA;
use Symfony\Component\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="station", indexes={
 *     @ORM\Index(name="idx_short_name", columns={"short_name"})
 * })
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks
 *
 * @AuditLog\Auditable
 *
 * @OA\Schema(type="object", schema="Station")
 * @AppAssert\StationPortChecker()
 */
class Station
{
    use Traits\TruncateStrings;

    public const DEFAULT_REQUEST_DELAY = 5;
    public const DEFAULT_REQUEST_THRESHOLD = 15;
    public const DEFAULT_DISCONNECT_DEACTIVATE_STREAMER = 0;
    public const DEFAULT_API_HISTORY_ITEMS = 5;

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
     * @ORM\Column(name="name", type="string", length=100, nullable=true)
     *
     * @OA\Property(example="AzuraTest Radio")
     *
     * @Assert\NotBlank()
     * @var string|null The full display name of the station.
     */
    protected $name;

    /**
     * @ORM\Column(name="short_name", type="string", length=100, nullable=true)
     *
     * @OA\Property(example="azuratest_radio")
     *
     * @Assert\NotBlank()
     * @var string|null The URL-friendly name for the station, typically auto-generated from the full station name.
     */
    protected $short_name;

    /**
     * @ORM\Column(name="is_enabled", type="boolean", nullable=false)
     *
     * @OA\Property(example=true)
     * @var bool If set to "false", prevents the station from broadcasting but leaves it in the database.
     */
    protected $is_enabled = true;

    /**
     * @ORM\Column(name="frontend_type", type="string", length=100, nullable=true)
     *
     * @OA\Property(example="icecast")
     *
     * @Assert\Choice(choices={Adapters::FRONTEND_ICECAST, Adapters::FRONTEND_REMOTE, Adapters::FRONTEND_SHOUTCAST})
     * @var string|null The frontend adapter (icecast,shoutcast,remote,etc)
     */
    protected $frontend_type = Adapters::FRONTEND_ICECAST;

    /**
     * @ORM\Column(name="frontend_config", type="json", nullable=true)
     *
     * @OA\Property(@OA\Items())
     * @var array|null An array containing station-specific frontend configuration
     */
    protected $frontend_config;

    /**
     * @ORM\Column(name="backend_type", type="string", length=100, nullable=true)
     *
     * @Assert\Choice(choices={Adapters::BACKEND_LIQUIDSOAP, Adapters::BACKEND_NONE})
     * @OA\Property(example="liquidsoap")
     * @var string|null The backend adapter (liquidsoap,etc)
     */
    protected $backend_type = Adapters::BACKEND_LIQUIDSOAP;

    /**
     * @ORM\Column(name="backend_config", type="json", nullable=true)
     *
     * @OA\Property(@OA\Items())
     * @var array|null An array containing station-specific backend configuration
     */
    protected $backend_config;

    /**
     * @ORM\Column(name="adapter_api_key", type="string", length=150, nullable=true)
     *
     * @AuditLog\AuditIgnore()
     *
     * @var string|null An internal API key used for container-to-container communications from Liquidsoap to AzuraCast
     */
    protected $adapter_api_key;

    /**
     * @ORM\Column(name="description", type="text", nullable=true)
     *
     * @OA\Property(example="A sample radio station.")
     * @var string|null
     */
    protected $description;

    /**
     * @ORM\Column(name="url", type="string", length=255, nullable=true)
     *
     * @OA\Property(example="https://demo.azuracast.com/")
     * @var string|null
     */
    protected $url;

    /**
     * @ORM\Column(name="genre", type="string", length=150, nullable=true)
     *
     * @OA\Property(example="Various")
     * @var string|null
     */
    protected $genre;

    /**
     * @ORM\Column(name="radio_base_dir", type="string", length=255, nullable=true)
     *
     * @OA\Property(example="/var/azuracast/stations/azuratest_radio")
     * @var string|null
     */
    protected $radio_base_dir;

    /**
     * @ORM\Column(name="nowplaying", type="array", nullable=true)
     *
     * @AuditLog\AuditIgnore()
     *
     * @var mixed|null
     */
    protected $nowplaying;

    /**
     * @ORM\Column(name="nowplaying_timestamp", type="integer", nullable=true)
     *
     * @AuditLog\AuditIgnore()
     *
     * @var int
     */
    protected $nowplaying_timestamp;

    /**
     * @ORM\Column(name="automation_settings", type="json", nullable=true)
     *
     * @OA\Property(@OA\Items())
     * @var array|null
     */
    protected $automation_settings;

    /**
     * @ORM\Column(name="automation_timestamp", type="integer", nullable=true)
     *
     * @AuditLog\AuditIgnore()
     *
     * @var int|null The UNIX timestamp when station automation was most recently run.
     */
    protected $automation_timestamp = 0;

    /**
     * @ORM\Column(name="enable_requests", type="boolean", nullable=false)
     *
     * @OA\Property(example=true)
     * @var bool Whether listeners can request songs to play on this station.
     */
    protected $enable_requests = false;

    /**
     * @ORM\Column(name="request_delay", type="integer", nullable=true)
     *
     * @OA\Property(example=5)
     * @var int|null
     */
    protected $request_delay = self::DEFAULT_REQUEST_DELAY;

    /**
     * @ORM\Column(name="request_threshold", type="integer", nullable=true)
     *
     * @OA\Property(example=15)
     * @var int|null
     */
    protected $request_threshold = self::DEFAULT_REQUEST_THRESHOLD;

    /**
     * @ORM\Column(name="disconnect_deactivate_streamer", type="integer", nullable=true, options={"default":0})
     *
     * @OA\Property(example=0)
     * @var int
     */
    protected $disconnect_deactivate_streamer = self::DEFAULT_DISCONNECT_DEACTIVATE_STREAMER;

    /**
     * @ORM\Column(name="enable_streamers", type="boolean", nullable=false)
     *
     * @OA\Property(example=false)
     * @var bool Whether streamers are allowed to broadcast to this station at all.
     */
    protected $enable_streamers = false;

    /**
     * @ORM\Column(name="is_streamer_live", type="boolean", nullable=false)
     *
     * @AuditLog\AuditIgnore()
     *
     * @OA\Property(example=false)
     * @var bool Whether a streamer is currently active on the station.
     */
    protected $is_streamer_live = false;

    /**
     * @ORM\Column(name="enable_public_page", type="boolean", nullable=false)
     *
     * @OA\Property(example=true)
     * @var bool Whether this station is visible as a public page and in a now-playing API response.
     */
    protected $enable_public_page = true;

    /**
     * @ORM\Column(name="enable_on_demand", type="boolean", nullable=false)
     *
     * @OA\Property(example=true)
     * @var bool Whether this station has a public "on-demand" streaming and download page.
     */
    protected $enable_on_demand = false;

    /**
     * @ORM\Column(name="needs_restart", type="boolean")
     *
     * @AuditLog\AuditIgnore()
     *
     * @var bool Whether to show the "Restart station to apply changes" sidebar for this station
     */
    protected $needs_restart = false;

    /**
     * @ORM\Column(name="has_started", type="boolean")
     *
     * @AuditLog\AuditIgnore()
     *
     * @var bool
     */
    protected $has_started = false;

    /**
     * @ORM\Column(name="api_history_items", type="smallint")
     *
     * @OA\Property(example=5)
     * @var int|null The number of "last played" history items to show for a station in the Now Playing API responses.
     */
    protected $api_history_items = self::DEFAULT_API_HISTORY_ITEMS;

    /**
     * @ORM\Column(name="timezone", type="string", length=100, nullable=true)
     *
     * @OA\Property(example="UTC")
     * @var string|null The time zone that station operations should take place in.
     */
    protected $timezone = 'UTC';

    /**
     * @ORM\Column(name="default_album_art_url", type="string", length=255, nullable=true)
     *
     * @OA\Property(example="https://example.com/image.jpg")
     * @var string|null The station-specific default album artwork URL.
     */
    protected $default_album_art_url = null;

    /**
     * @ORM\OneToMany(targetEntity="SongHistory", mappedBy="station")
     * @ORM\OrderBy({"timestamp_start" = "DESC"})
     * @var Collection
     */
    protected $history;

    /**
     * @ORM\ManyToOne(targetEntity="StorageLocation")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="media_storage_location_id", referencedColumnName="id", onDelete="SET NULL")
     * })
     *
     * @DeepNormalize(true)
     * @Serializer\MaxDepth(1)
     *
     * @var StorageLocation
     */
    protected $media_storage_location;

    /**
     * @ORM\ManyToOne(targetEntity="StorageLocation")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="recordings_storage_location_id", referencedColumnName="id", onDelete="SET NULL")
     * })
     *
     * @DeepNormalize(true)
     * @Serializer\MaxDepth(1)
     *
     * @var StorageLocation
     */
    protected $recordings_storage_location;

    /**
     * @ORM\OneToMany(targetEntity="StationStreamer", mappedBy="station")
     * @var Collection
     */
    protected $streamers;

    /**
     * @ORM\Column(name="current_streamer_id", type="integer", nullable=true)
     * @var int|null
     */
    protected $current_streamer_id;

    /**
     * @ORM\ManyToOne(targetEntity="StationStreamer")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="current_streamer_id", referencedColumnName="id", onDelete="SET NULL")
     * })
     * @var StationStreamer|null
     */
    protected $current_streamer;

    /**
     * @ORM\OneToMany(targetEntity="RolePermission", mappedBy="station")
     * @var Collection
     */
    protected $permissions;

    /**
     * @ORM\OneToMany(targetEntity="StationPlaylist", mappedBy="station")
     * @ORM\OrderBy({"type" = "ASC","weight" = "DESC"})
     * @var Collection
     */
    protected $playlists;

    /**
     * @ORM\OneToMany(targetEntity="StationMount", mappedBy="station")
     * @var Collection
     */
    protected $mounts;

    /**
     * @ORM\OneToMany(targetEntity="StationRemote", mappedBy="station")
     * @var Collection
     */
    protected $remotes;

    /**
     * @ORM\OneToMany(targetEntity="StationWebhook", mappedBy="station", fetch="EXTRA_LAZY", cascade={"persist"})
     * @var Collection
     */
    protected $webhooks;

    /**
     * @ORM\OneToMany(targetEntity="SftpUser", mappedBy="station")
     * @var Collection
     */
    protected $sftp_users;

    public function __construct()
    {
        $this->history = new ArrayCollection();
        $this->playlists = new ArrayCollection();
        $this->mounts = new ArrayCollection();
        $this->remotes = new ArrayCollection();
        $this->webhooks = new ArrayCollection();
        $this->streamers = new ArrayCollection();
        $this->sftp_users = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name = null): void
    {
        $this->name = $this->truncateString($name, 100);

        if (empty($this->short_name) && !empty($name)) {
            $this->setShortName($name);
        }
    }

    public function getShortName(): ?string
    {
        return (!empty($this->short_name))
            ? $this->short_name
            : self::getStationShortName($this->name);
    }

    public function setShortName(?string $short_name): void
    {
        $short_name = trim($short_name);
        if (!empty($short_name)) {
            $short_name = self::getStationShortName($short_name);
            $this->short_name = $this->truncateString($short_name, 100);
        }
    }

    public static function getStationShortName(string $str): string
    {
        return File::sanitizeFileName($str);
    }

    public function setIsEnabled(bool $is_enabled): void
    {
        $this->is_enabled = $is_enabled;
    }

    public function getFrontendType(): ?string
    {
        return $this->frontend_type;
    }

    public function setFrontendType(?string $frontend_type = null): void
    {
        $this->frontend_type = $frontend_type;
    }

    public function getFrontendConfig(): StationFrontendConfiguration
    {
        return new StationFrontendConfiguration((array)$this->frontend_config);
    }

    /**
     * @param array|StationFrontendConfiguration $frontend_config
     * @param bool $force_overwrite
     */
    public function setFrontendConfig($frontend_config, bool $force_overwrite = false): void
    {
        if (!($frontend_config instanceof StationFrontendConfiguration)) {
            $config = new StationFrontendConfiguration(
                ($force_overwrite) ? [] : (array)$this->frontend_config,
            );

            foreach ($frontend_config as $key => $val) {
                $config->set($key, $val);
            }

            $frontend_config = $config;
        }

        $config = $frontend_config->toArray();

        if ($this->frontend_config != $config) {
            $this->setNeedsRestart(true);
        }

        $this->frontend_config = $config;
    }

    /**
     * Set frontend configuration but do not overwrite existing values.
     *
     * @param array $default_config
     */
    public function setFrontendConfigDefaults(array $default_config): void
    {
        $frontend_config = (array)$this->frontend_config;

        foreach ($default_config as $config_key => $config_value) {
            if (empty($frontend_config[$config_key])) {
                $frontend_config[$config_key] = $config_value;
            }
        }

        $this->frontend_config = $frontend_config;
    }

    public function getBackendType(): ?string
    {
        return $this->backend_type;
    }

    public function setBackendType(string $backend_type = null): void
    {
        $this->backend_type = $backend_type;
    }

    /**
     * Clear all port assignments for the station (useful after cloning).
     */
    public function clearPorts(): void
    {
        $fe_config = $this->getFrontendConfig();
        $fe_config->setPort(null);
        $this->setFrontendConfig($fe_config);

        $be_config = $this->getBackendConfig();
        $be_config->setDjPort(null);
        $be_config->setTelnetPort(null);
        $this->setBackendConfig($be_config);
    }

    /**
     * Whether the station uses AzuraCast to directly manage the AutoDJ or lets the backend handle it.
     */
    public function useManualAutoDJ(): bool
    {
        $settings = $this->getBackendConfig();
        return $settings->useManualAutoDj();
    }

    public function getBackendConfig(): StationBackendConfiguration
    {
        return new StationBackendConfiguration((array)$this->backend_config);
    }

    /**
     * @param array|StationBackendConfiguration $backend_config
     * @param bool $force_overwrite
     */
    public function setBackendConfig($backend_config, bool $force_overwrite = false): void
    {
        if (!($backend_config instanceof StationBackendConfiguration)) {
            $config = new StationBackendConfiguration(
                ($force_overwrite) ? [] : (array)$this->backend_config
            );

            foreach ($backend_config as $key => $val) {
                $config->set($key, $val);
            }

            $backend_config = $config;
        }

        $config = $backend_config->toArray();

        if ($this->backend_config != $config) {
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
     * @param string $api_key
     */
    public function validateAdapterApiKey($api_key): bool
    {
        return hash_equals($api_key, $this->adapter_api_key);
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
        $this->url = $this->truncateString($url);
    }

    public function getGenre(): ?string
    {
        return $this->genre;
    }

    public function setGenre(?string $genre): void
    {
        $this->genre = $this->truncateString($genre, 150);
    }

    public function getRadioBaseDir(): ?string
    {
        return $this->radio_base_dir;
    }

    public function setRadioBaseDir(?string $newDir = null): void
    {
        $newDir = $this->truncateString(trim($newDir));

        if (empty($newDir)) {
            $stationsBaseDir = Settings::getInstance()->getStationDirectory();
            $newDir = $stationsBaseDir . '/' . $this->getShortName();
        }

        $this->radio_base_dir = $newDir;
    }

    public function ensureDirectoriesExist(): void
    {
        if (null === $this->radio_base_dir) {
            $this->setRadioBaseDir(null);
        }

        // Flysystem adapters will automatically create the main directory.
        $this->getRadioBaseDirAdapter();
        $this->getRadioPlaylistsDirAdapter();
        $this->getRadioConfigDirAdapter();
        $this->getRadioTempDirAdapter();

        if (null === $this->media_storage_location) {
            $storageLocation = new StorageLocation(
                StorageLocation::TYPE_STATION_MEDIA,
                StorageLocation::ADAPTER_LOCAL
            );
            $storageLocation->setPath($this->getRadioBaseDir() . '/media');

            $this->media_storage_location = $storageLocation;
        }

        if (null === $this->recordings_storage_location) {
            $storageLocation = new StorageLocation(
                StorageLocation::TYPE_STATION_RECORDINGS,
                StorageLocation::ADAPTER_LOCAL
            );
            $storageLocation->setPath($this->getRadioBaseDir() . '/recordings');

            $this->recordings_storage_location = $storageLocation;
        }

        $this->getRadioMediaDirAdapter();
        $this->getRadioRecordingsDirAdapter();
    }

    public function getRadioBaseDirAdapter(?string $suffix = null): AdapterInterface
    {
        $path = $this->radio_base_dir . $suffix;
        return new Local($path);
    }

    public function getRadioPlaylistsDir(): string
    {
        return $this->radio_base_dir . '/playlists';
    }

    public function getRadioPlaylistsDirAdapter(): AdapterInterface
    {
        return new Local($this->getRadioPlaylistsDir());
    }

    public function getRadioConfigDir(): string
    {
        return $this->radio_base_dir . '/config';
    }

    public function getRadioConfigDirAdapter(): AdapterInterface
    {
        return new Local($this->getRadioConfigDir());
    }

    public function getRadioTempDir(): string
    {
        return $this->radio_base_dir . '/temp';
    }

    public function getRadioTempDirAdapter(): AdapterInterface
    {
        return new Local($this->getRadioTempDir());
    }

    public function getRadioMediaDirAdapter(): AdapterInterface
    {
        return $this->getMediaStorageLocation()->getStorageAdapter();
    }

    public function getRadioRecordingsDirAdapter(): AdapterInterface
    {
        return $this->getRecordingsStorageLocation()->getStorageAdapter();
    }

    public function getNowplaying(): ?Api\NowPlaying
    {
        if ($this->nowplaying instanceof Api\NowPlaying) {
            return $this->nowplaying;
        }
        return null;
    }

    public function setNowplaying(Api\NowPlaying $nowplaying = null): void
    {
        $this->nowplaying = $nowplaying;

        if ($nowplaying instanceof Api\NowPlaying) {
            $this->nowplaying_timestamp = time();
        }
    }

    public function getNowplayingTimestamp(): int
    {
        return (int)$this->nowplaying_timestamp;
    }

    public function setNowPlayingTimestamp(int $nowplaying_timestamp): void
    {
        $this->nowplaying_timestamp = $nowplaying_timestamp;
    }

    /**
     * @return mixed[]|null
     */
    public function getAutomationSettings(): ?array
    {
        return $this->automation_settings;
    }

    public function setAutomationSettings(array $automation_settings = null): void
    {
        $this->automation_settings = $automation_settings;
    }

    public function getAutomationTimestamp(): ?int
    {
        return $this->automation_timestamp;
    }

    public function setAutomationTimestamp(int $automation_timestamp = null): void
    {
        $this->automation_timestamp = $automation_timestamp;
    }

    public function getEnableRequests(): bool
    {
        return $this->enable_requests;
    }

    public function setEnableRequests(bool $enable_requests): void
    {
        $this->enable_requests = $enable_requests;
    }

    public function getRequestDelay(): ?int
    {
        return $this->request_delay;
    }

    public function setRequestDelay(int $request_delay = null): void
    {
        $this->request_delay = $request_delay;
    }

    public function getRequestThreshold(): ?int
    {
        return $this->request_threshold;
    }

    public function setRequestThreshold(int $request_threshold = null): void
    {
        $this->request_threshold = $request_threshold;
    }

    public function getDisconnectDeactivateStreamer(): int
    {
        return $this->disconnect_deactivate_streamer;
    }

    public function setDisconnectDeactivateStreamer(int $disconnect_deactivate_streamer): void
    {
        $this->disconnect_deactivate_streamer = $disconnect_deactivate_streamer;
    }

    public function getEnableStreamers(): bool
    {
        return $this->enable_streamers;
    }

    public function setEnableStreamers(bool $enable_streamers): void
    {
        if ($this->enable_streamers !== $enable_streamers) {
            $this->setNeedsRestart(true);
        }

        $this->enable_streamers = $enable_streamers;
    }

    public function getIsStreamerLive(): bool
    {
        return $this->is_streamer_live;
    }

    public function setIsStreamerLive(bool $is_streamer_live): void
    {
        $this->is_streamer_live = $is_streamer_live;
    }

    public function getEnablePublicPage(): bool
    {
        return (bool)$this->enable_public_page && $this->isEnabled();
    }

    public function setEnablePublicPage(bool $enable_public_page): void
    {
        $this->enable_public_page = $enable_public_page;
    }

    public function getEnableOnDemand(): bool
    {
        return $this->enable_on_demand;
    }

    public function setEnableOnDemand(bool $enable_on_demand): void
    {
        $this->enable_on_demand = $enable_on_demand;
    }

    public function isEnabled(): bool
    {
        return $this->is_enabled;
    }

    public function getNeedsRestart(): bool
    {
        return $this->needs_restart;
    }

    public function setNeedsRestart(bool $needs_restart): void
    {
        $this->needs_restart = $needs_restart;
    }

    public function getHasStarted(): bool
    {
        return $this->has_started;
    }

    public function setHasStarted(bool $has_started): void
    {
        $this->has_started = $has_started;
    }

    public function getApiHistoryItems(): int
    {
        return $this->api_history_items ?? self::DEFAULT_API_HISTORY_ITEMS;
    }

    public function setApiHistoryItems(?int $api_history_items): void
    {
        $this->api_history_items = $api_history_items;
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

    public function getDefaultAlbumArtUrl(): ?string
    {
        return $this->default_album_art_url;
    }

    /**
     * @param string|null $default_album_art_url
     */
    public function setDefaultAlbumArtUrl(?string $default_album_art_url): void
    {
        $this->default_album_art_url = $default_album_art_url;
    }

    public function getHistory(): Collection
    {
        return $this->history;
    }

    public function getStreamers(): Collection
    {
        return $this->streamers;
    }

    public function getCurrentStreamer(): ?StationStreamer
    {
        return $this->current_streamer;
    }

    public function setCurrentStreamer(?StationStreamer $current_streamer = null): void
    {
        if (null !== $this->current_streamer || null !== $current_streamer) {
            $this->current_streamer = $current_streamer;
        }
    }

    public function getMediaStorageLocation(): StorageLocation
    {
        return $this->media_storage_location;
    }

    public function setMediaStorageLocation(StorageLocation $storageLocation): void
    {
        if (StorageLocation::TYPE_STATION_MEDIA !== $storageLocation->getType()) {
            throw new \InvalidArgumentException('Storage location must be for station media.');
        }

        $this->media_storage_location = $storageLocation;
    }

    public function getRecordingsStorageLocation(): StorageLocation
    {
        return $this->recordings_storage_location;
    }

    public function setRecordingsStorageLocation(StorageLocation $storageLocation): void
    {
        if (StorageLocation::TYPE_STATION_RECORDINGS !== $storageLocation->getType()) {
            throw new \InvalidArgumentException('Storage location must be for station live recordings.');
        }

        $this->recordings_storage_location = $storageLocation;
    }

    public function getPermissions(): Collection
    {
        return $this->permissions;
    }

    /**
     * @return StationMedia[]|Collection
     */
    public function getMedia(): Collection
    {
        return $this->media_storage_location->getMedia();
    }

    /**
     * @return StationPlaylist[]|Collection
     */
    public function getPlaylists(): Collection
    {
        return $this->playlists;
    }

    /**
     * @return StationMount[]|Collection
     */
    public function getMounts(): Collection
    {
        return $this->mounts;
    }

    /**
     * @return StationRemote[]|Collection
     */
    public function getRemotes(): Collection
    {
        return $this->remotes;
    }

    public function getWebhooks(): Collection
    {
        return $this->webhooks;
    }

    public function getSftpUsers(): Collection
    {
        return $this->sftp_users;
    }

    public function clearCache(): void
    {
        $this->nowplaying = null;
        $this->nowplaying_timestamp = 0;
    }
}
