<?php
namespace App\Entity;

use App\Annotations\AuditLog;
use App\Customization;
use App\Radio\Adapters;
use App\Radio\Frontend\AbstractFrontend;
use App\Radio\Quota;
use App\Radio\Remote\AdapterProxy;
use App\Validator\Constraints as AppAssert;
use Azura\File;
use Brick\Math\BigInteger;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use OpenApi\Annotations as OA;
use Psr\Http\Message\UriInterface;
use RuntimeException;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="station")
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
    public const DEFAULT_REQUEST_DELAY = 5;
    public const DEFAULT_REQUEST_THRESHOLD = 15;
    public const DEFAULT_DISCONNECT_DEACTIVATE_STREAMER = 0;
    public const DEFAULT_API_HISTORY_ITEMS = 5;

    use Traits\TruncateStrings;

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
     * @ORM\Column(name="frontend_config", type="json_array", nullable=true)
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
     * @ORM\Column(name="backend_config", type="json_array", nullable=true)
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
     * @var string|null An internal-use API key used for container-to-container communications from Liquidsoap to AzuraCast
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
     * @ORM\Column(name="radio_media_dir", type="string", length=255, nullable=true)
     *
     * @OA\Property(example="/var/azuracast/stations/azuratest_radio/media")
     * @var string|null
     */
    protected $radio_media_dir;

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
     * @ORM\Column(name="automation_settings", type="json_array", nullable=true)
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
     * @var int|null The number of "last played" history items to show for a given station in the Now Playing API responses.
     */
    protected $api_history_items = self::DEFAULT_API_HISTORY_ITEMS;

    /**
     * @ORM\Column(name="storage_quota", type="bigint", nullable=true)
     *
     * @OA\Property(example="50 GB")
     * @var string|null
     */
    protected $storage_quota;

    /**
     * @OA\Property(example="50000000000")
     * @var string|null
     */
    protected $storage_quota_bytes;

    /**
     * @ORM\Column(name="storage_used", type="bigint", nullable=true)
     *
     * @AuditLog\AuditIgnore()
     *
     * @OA\Property(example="1 GB")
     * @var string|null
     */
    protected $storage_used;

    /**
     * @OA\Property(example="1000000000")
     * @var string|null
     */
    protected $storage_used_bytes;

    /**
     * @ORM\Column(name="timezone", type="string", length=100, nullable=true)
     *
     * @OA\Property(example="UTC")
     * @var string|null The time zone that station operations should take place in.
     */
    protected $timezone = 'UTC';

    /**
     * @ORM\OneToMany(targetEntity="SongHistory", mappedBy="station")
     * @ORM\OrderBy({"timestamp_start" = "DESC"})
     * @var Collection
     */
    protected $history;

    /**
     * @ORM\OneToMany(targetEntity="StationMedia", mappedBy="station")
     * @var Collection
     */
    protected $media;

    /**
     * @ORM\OneToMany(targetEntity="StationStreamer", mappedBy="station")
     * @var Collection
     */
    protected $streamers;

    /**
     * @ORM\Column(name="current_streamer_id", type="integer", nullable=true)
     * @var int
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
    protected $sftpUsers;

    public function __construct()
    {
        $this->history = new ArrayCollection;
        $this->media = new ArrayCollection;
        $this->playlists = new ArrayCollection;
        $this->mounts = new ArrayCollection;
        $this->remotes = new ArrayCollection;
        $this->webhooks = new ArrayCollection;
        $this->streamers = new ArrayCollection;
        $this->sftpUsers = new ArrayCollection;
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return null|string
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param null|string $name
     */
    public function setName(?string $name = null): void
    {
        $this->name = $this->_truncateString($name, 100);

        if (empty($this->short_name) && !empty($name)) {
            $this->setShortName(self::getStationShortName($name));
        }
    }

    public static function getStationShortName(string $str): string
    {
        return File::sanitizeFileName($str);
    }

    /**
     * @param bool $is_enabled
     */
    public function setIsEnabled(bool $is_enabled): void
    {
        $this->is_enabled = $is_enabled;
    }

    /**
     * @return null|string
     */
    public function getFrontendType(): ?string
    {
        return $this->frontend_type;
    }

    /**
     * @param null|string $frontend_type
     */
    public function setFrontendType(string $frontend_type = null): void
    {
        $this->frontend_type = $frontend_type;
    }

    /**
     * @return array|null
     */
    public function getFrontendConfig(): ?array
    {
        return $this->frontend_config;
    }

    /**
     * @param array $frontend_config
     * @param bool $force_overwrite
     */
    public function setFrontendConfig(array $frontend_config, $force_overwrite = false): void
    {
        $config = ($force_overwrite) ? [] : (array)$this->frontend_config;
        foreach ($frontend_config as $cfg_key => $cfg_val) {
            $config[$cfg_key] = $cfg_val;
        }

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

    /**
     * @return null|string
     */
    public function getBackendType(): ?string
    {
        return $this->backend_type;
    }

    /**
     * @param null|string $backend_type
     */
    public function setBackendType(string $backend_type = null): void
    {
        $this->backend_type = $backend_type;
    }

    /**
     * Clear all port assignments for the station (useful after cloning).
     */
    public function clearPorts(): void
    {
        $fe_config = (array)$this->frontend_config;
        unset($fe_config['port']);

        $this->frontend_config = $fe_config;

        $be_config = (array)$this->backend_config;
        unset($be_config['dj_port'], $be_config['telnet_port']);

        $this->backend_config = $be_config;
    }

    /**
     * Whether the station uses AzuraCast to directly manage the AutoDJ or lets the backend handle it.
     *
     * @return bool
     */
    public function useManualAutoDJ(): bool
    {
        $settings = (array)$this->getBackendConfig();
        return (bool)($settings['use_manual_autodj'] ?? false);
    }

    /**
     * @return array|null
     */
    public function getBackendConfig(): ?array
    {
        return $this->backend_config;
    }

    /**
     * @param array $backend_config
     * @param bool $force_overwrite
     */
    public function setBackendConfig(array $backend_config, $force_overwrite = false): void
    {
        $config = ($force_overwrite) ? [] : (array)$this->backend_config;
        foreach ($backend_config as $cfg_key => $cfg_val) {
            $config[$cfg_key] = $cfg_val;
        }

        if ($this->backend_config != $config) {
            $this->setNeedsRestart(true);
        }

        $this->backend_config = $config;
    }

    /**
     * @return null|string
     */
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
     *
     * @return bool
     */
    public function validateAdapterApiKey($api_key): bool
    {
        return hash_equals($api_key, $this->adapter_api_key);
    }

    /**
     * @return null|string
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param null|string $description
     */
    public function setDescription(string $description = null): void
    {
        $this->description = $description;
    }

    /**
     * @return null|string
     */
    public function getUrl(): ?string
    {
        return $this->url;
    }

    /**
     * @param null|string $url
     */
    public function setUrl(string $url = null): void
    {
        $this->url = $this->_truncateString($url);
    }

    /**
     * @return string|null
     */
    public function getGenre(): ?string
    {
        return $this->genre;
    }

    /**
     * @param string|null $genre
     */
    public function setGenre(?string $genre): void
    {
        $this->genre = $this->_truncateString($genre, 150);
    }

    /**
     * @return null|string
     */
    public function getRadioBaseDir(): ?string
    {
        return $this->radio_base_dir;
    }

    /**
     * @param string $new_dir
     */
    public function setRadioBaseDir($new_dir): void
    {
        $this->radio_base_dir = $this->_truncateString(trim($new_dir));
    }

    public function getRadioAlbumArtDir(): string
    {
        return $this->radio_base_dir . '/album_art';
    }

    public function getRadioTempDir(): string
    {
        return $this->radio_base_dir . '/temp';
    }

    public function getRadioRecordingsDir(): string
    {
        return $this->radio_base_dir . '/recordings';
    }

    /**
     * Given an absolute path, return a path relative to this station's media directory.
     *
     * @param string $full_path
     *
     * @return string
     */
    public function getRelativeMediaPath($full_path): string
    {
        return ltrim(str_replace($this->getRadioMediaDir(), '', $full_path), '/');
    }

    /**
     * @return string
     */
    public function getRadioMediaDir(): string
    {
        return (!empty($this->radio_media_dir))
            ? $this->radio_media_dir
            : $this->radio_base_dir . '/media';
    }

    /**
     * @param string $new_dir
     */
    public function setRadioMediaDir(?string $new_dir): void
    {
        $new_dir = $this->_truncateString(trim($new_dir));

        if ($new_dir && $new_dir !== $this->radio_media_dir) {
            if (!empty($new_dir) && !file_exists($new_dir) && !mkdir($new_dir, 0777, true) && !is_dir($new_dir)) {
                throw new RuntimeException(sprintf('Directory "%s" was not created', $new_dir));
            }

            $this->radio_media_dir = $new_dir;
        }
    }

    /**
     * @return string
     */
    public function getRadioPlaylistsDir(): string
    {
        return $this->radio_base_dir . '/playlists';
    }

    /**
     * @return string
     */
    public function getRadioConfigDir(): string
    {
        return $this->radio_base_dir . '/config';
    }

    /**
     * @return Api\NowPlaying|null
     */
    public function getNowplaying(): ?Api\NowPlaying
    {
        if ($this->nowplaying instanceof Api\NowPlaying) {
            return $this->nowplaying;
        }
        return null;
    }

    /**
     * @param Api\NowPlaying|null $nowplaying
     */
    public function setNowplaying(Api\NowPlaying $nowplaying = null): void
    {
        $this->nowplaying = $nowplaying;

        if ($nowplaying instanceof Api\NowPlaying) {
            $this->nowplaying_timestamp = time();
        }
    }

    /**
     * @return int
     */
    public function getNowplayingTimestamp(): int
    {
        return (int)$this->nowplaying_timestamp;
    }

    /**
     * @param int $nowplaying_timestamp
     */
    public function setNowPlayingTimestamp(int $nowplaying_timestamp): void
    {
        $this->nowplaying_timestamp = $nowplaying_timestamp;
    }

    /**
     * @return array|null
     */
    public function getAutomationSettings(): ?array
    {
        return $this->automation_settings;
    }

    /**
     * @param array|null $automation_settings
     */
    public function setAutomationSettings(array $automation_settings = null): void
    {
        $this->automation_settings = $automation_settings;
    }

    /**
     * @return int|null
     */
    public function getAutomationTimestamp(): ?int
    {
        return $this->automation_timestamp;
    }

    /**
     * @param int|null $automation_timestamp
     */
    public function setAutomationTimestamp(int $automation_timestamp = null): void
    {
        $this->automation_timestamp = $automation_timestamp;
    }

    /**
     * @return bool
     */
    public function getEnableRequests(): bool
    {
        return $this->enable_requests;
    }

    /**
     * @param bool $enable_requests
     */
    public function setEnableRequests(bool $enable_requests): void
    {
        $this->enable_requests = $enable_requests;
    }

    /**
     * @return int|null
     */
    public function getRequestDelay(): ?int
    {
        return $this->request_delay;
    }

    /**
     * @param int|null $request_delay
     */
    public function setRequestDelay(int $request_delay = null): void
    {
        $this->request_delay = $request_delay;
    }

    /**
     * @return int|null
     */
    public function getRequestThreshold(): ?int
    {
        return $this->request_threshold;
    }

    /**
     * @param int|null $request_threshold
     */
    public function setRequestThreshold(int $request_threshold = null): void
    {
        $this->request_threshold = $request_threshold;
    }

    /**
     * @return int
     */
    public function getDisconnectDeactivateStreamer(): int
    {
        return $this->disconnect_deactivate_streamer;
    }

    /**
     * @param int $disconnect_deactivate_streamer
     */
    public function setDisconnectDeactivateStreamer(int $disconnect_deactivate_streamer): void
    {
        $this->disconnect_deactivate_streamer = $disconnect_deactivate_streamer;
    }

    /**
     * @return bool
     */
    public function getEnableStreamers(): bool
    {
        return $this->enable_streamers;
    }

    /**
     * @param bool $enable_streamers
     */
    public function setEnableStreamers(bool $enable_streamers): void
    {
        if ($this->enable_streamers !== $enable_streamers) {
            $this->setNeedsRestart(true);
        }

        $this->enable_streamers = $enable_streamers;
    }

    /**
     * @return bool
     */
    public function getIsStreamerLive(): bool
    {
        return $this->is_streamer_live;
    }

    /**
     * @param bool $is_streamer_live
     */
    public function setIsStreamerLive(bool $is_streamer_live): void
    {
        $this->is_streamer_live = $is_streamer_live;
    }

    /**
     * @return bool
     */
    public function getEnablePublicPage(): bool
    {
        return (bool)$this->enable_public_page && $this->isEnabled();
    }

    /**
     * @param bool $enable_public_page
     */
    public function setEnablePublicPage(bool $enable_public_page): void
    {
        $this->enable_public_page = $enable_public_page;
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->is_enabled;
    }

    /**
     * @return bool
     */
    public function getNeedsRestart(): bool
    {
        return $this->needs_restart;
    }

    /**
     * @param bool $needs_restart
     */
    public function setNeedsRestart(bool $needs_restart): void
    {
        $this->needs_restart = $needs_restart;
    }

    /**
     * @return bool
     */
    public function getHasStarted(): bool
    {
        return $this->has_started;
    }

    /**
     * @param bool $has_started
     */
    public function setHasStarted(bool $has_started): void
    {
        $this->has_started = $has_started;
    }

    /**
     * @return int
     */
    public function getApiHistoryItems(): int
    {
        return $this->api_history_items ?? self::DEFAULT_API_HISTORY_ITEMS;
    }

    /**
     * @param int|null $api_history_items
     */
    public function setApiHistoryItems(?int $api_history_items): void
    {
        $this->api_history_items = $api_history_items;
    }

    /**
     * @return string|null
     */
    public function getStorageQuota(): ?string
    {
        $raw_quota = $this->getStorageQuotaBytes();

        return ($raw_quota instanceof BigInteger)
            ? Quota::getReadableSize($raw_quota)
            : '';
    }

    /**
     * @param BigInteger|string|null $storage_quota
     */
    public function setStorageQuota($storage_quota): void
    {
        $storage_quota = (string)Quota::convertFromReadableSize($storage_quota);
        $this->storage_quota = !empty($storage_quota) ? $storage_quota : null;
    }

    /**
     * @return BigInteger|null
     */
    public function getStorageQuotaBytes(): ?BigInteger
    {
        $size = $this->storage_quota;

        return (null !== $size)
            ? BigInteger::of($size)
            : null;
    }

    /**
     * @return string|null
     */
    public function getStorageUsed(): ?string
    {
        $raw_size = $this->getStorageUsedBytes();

        return ($raw_size instanceof BigInteger)
            ? Quota::getReadableSize($raw_size)
            : '';
    }

    /**
     * @param BigInteger|string|null $storage_used
     */
    public function setStorageUsed($storage_used): void
    {
        $storage_used = (string)Quota::convertFromReadableSize($storage_used);
        $this->storage_used = !empty($storage_used) ? $storage_used : null;
    }

    /**
     * @return BigInteger
     */
    public function getStorageUsedBytes(): BigInteger
    {
        $size = $this->storage_used;

        if (null === $size) {
            return BigInteger::zero();
        }

        return BigInteger::of($size);
    }

    /**
     * Increment the current used storage total.
     *
     * @param BigInteger|string|int $new_storage_amount
     */
    public function addStorageUsed($new_storage_amount): void
    {
        if (empty($new_storage_amount)) {
            return;
        }

        $current_storage_used = $this->getStorageUsedBytes();
        $this->storage_used = (string)$current_storage_used->plus($new_storage_amount);
    }

    /**
     * Decrement the current used storage total.
     *
     * @param BigInteger|string|int $amount_to_remove
     */
    public function removeStorageUsed($amount_to_remove): void
    {
        if (empty($amount_to_remove)) {
            return;
        }

        $current_storage_used = $this->getStorageUsedBytes();
        $storage_used = $current_storage_used->minus($amount_to_remove);
        if ($storage_used->isLessThan(0)) {
            $storage_used = BigInteger::zero();
        }

        $this->storage_used = (string)$storage_used;
    }

    /**
     * @return string
     */
    public function getStorageAvailable(): string
    {
        $raw_size = $this->getRawStorageAvailable();

        return ($raw_size instanceof BigInteger)
            ? Quota::getReadableSize($raw_size)
            : '';
    }

    /**
     * @return BigInteger|null
     */
    public function getRawStorageAvailable(): ?BigInteger
    {
        $quota = $this->getStorageQuotaBytes();
        $total_space = disk_total_space($this->getRadioMediaDir());

        if ($quota === null || $quota->compareTo($total_space) === 1) {
            return BigInteger::of($total_space);
        }

        return $quota;
    }

    /**
     * @return bool
     */
    public function isStorageFull(): bool
    {
        $available = $this->getRawStorageAvailable();
        if ($available === null) {
            return true;
        }

        $used = $this->getStorageUsedBytes();
        if ($used === null) {
            return false;
        }

        return ($used->compareTo($available) !== -1);
    }

    /**
     * @return int
     */
    public function getStorageUsePercentage(): int
    {
        return Quota::getPercentage($this->getStorageUsedBytes(), $this->getRawStorageAvailable());
    }

    /**
     * @return string
     */
    public function getTimezone(): string
    {
        if (!empty($this->timezone)) {
            return $this->timezone;
        }

        return Customization::DEFAULT_TIMEZONE;
    }

    /**
     * @param string|null $timezone
     */
    public function setTimezone(?string $timezone): void
    {
        $this->timezone = $timezone;
    }

    /**
     * @return Collection
     */
    public function getHistory(): Collection
    {
        return $this->history;
    }

    /**
     * @return Collection
     */
    public function getMedia(): Collection
    {
        return $this->media;
    }

    /**
     * @return Collection
     */
    public function getStreamers(): Collection
    {
        return $this->streamers;
    }

    /**
     * @return StationStreamer|null
     */
    public function getCurrentStreamer(): ?StationStreamer
    {
        return $this->current_streamer;
    }

    /**
     * @param StationStreamer|null $current_streamer
     */
    public function setCurrentStreamer(?StationStreamer $current_streamer = null): void
    {
        $this->current_streamer = $current_streamer;
    }

    /**
     * @return Collection
     */
    public function getPermissions(): Collection
    {
        return $this->permissions;
    }

    /**
     * @return Collection
     */
    public function getPlaylists(): Collection
    {
        return $this->playlists;
    }

    /**
     * @return Collection
     */
    public function getMounts(): Collection
    {
        return $this->mounts;
    }

    /**
     * @return Collection
     */
    public function getRemotes(): Collection
    {
        return $this->remotes;
    }

    /**
     * @return Collection
     */
    public function getWebhooks(): Collection
    {
        return $this->webhooks;
    }

    /**
     * @return Collection
     */
    public function getSftpUsers(): Collection
    {
        return $this->sftpUsers;
    }

    /**
     * Retrieve the API version of the object/array.
     *
     * @param AbstractFrontend $fa
     * @param AdapterProxy[] $remote_adapters
     * @param UriInterface|null $base_url
     * @param bool $is_nowplaying
     *
     * @return Api\Station
     */
    public function api(
        AbstractFrontend $fa,
        array $remote_adapters = [],
        UriInterface $base_url = null,
        bool $is_nowplaying = false
    ): Api\Station {
        $response = new Api\Station;
        $response->id = (int)$this->id;
        $response->name = (string)$this->name;
        $response->shortcode = (string)$this->getShortName();
        $response->description = (string)$this->description;
        $response->frontend = (string)$this->frontend_type;
        $response->backend = (string)$this->backend_type;
        $response->is_public = (bool)$this->enable_public_page;
        $response->listen_url = $fa->getStreamUrl($this, $base_url);

        $mounts = [];
        if ($fa::supportsMounts() && $this->mounts->count() > 0) {
            foreach ($this->mounts as $mount) {
                /** @var StationMount $mount */
                if ($mount->isVisibleOnPublicPages()) {
                    $mounts[] = $mount->api($fa, $base_url);
                }
            }
        }
        $response->mounts = $mounts;

        $remotes = [];
        foreach ($remote_adapters as $ra_proxy) {
            $remote = $ra_proxy->getRemote();
            if ($remote->isVisibleOnPublicPages()) {
                $remotes[] = $remote->api($ra_proxy->getAdapter());
            }
        }
        $response->remotes = $remotes;

        return $response;
    }

    /**
     * @return null|string
     */
    public function getShortName(): ?string
    {
        return (!empty($this->short_name))
            ? $this->short_name
            : self::getStationShortName($this->name);
    }

    /**
     * @param null|string $short_name
     */
    public function setShortName(?string $short_name): void
    {
        $short_name = trim($short_name);
        if (!empty($short_name)) {
            $this->short_name = $this->_truncateString($short_name, 100);
        }
    }
}
