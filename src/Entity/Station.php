<?php
namespace App\Entity;

use App\Radio\Quota;
use Brick\Math\BigInteger;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use Doctrine\ORM\Mapping as ORM;
use OpenApi\Annotations as OA;

use App\Radio\Frontend\AbstractFrontend;
use App\Radio\Remote\AdapterProxy;
use App\Radio\Remote\AbstractRemote;
use Doctrine\ORM\EntityManager;
use GuzzleHttp\Psr7\Uri;
use Interop\Container\ContainerInterface;
use Psr\Http\Message\UriInterface;

/**
 * @ORM\Table(name="station")
 * @ORM\Entity(repositoryClass="App\Entity\Repository\StationRepository")
 * @ORM\HasLifecycleCallbacks
 *
 * @OA\Schema(type="object", schema="Station")
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
     * @var int
     */
    protected $id;

    /**
     * @ORM\Column(name="name", type="string", length=100, nullable=true)
     * @var string|null The full display name of the station.
     */
    protected $name;

    /**
     * @ORM\Column(name="short_name", type="string", length=100, nullable=true)
     * @var string|null The URL-friendly name for the station, typically auto-generated from the full station name.
     */
    protected $short_name;

    /**
     * @ORM\Column(name="is_enabled", type="boolean", nullable=false)
     * @var bool If set to "false", prevents the station from broadcasting but leaves it in the database.
     */
    protected $is_enabled = true;

    /**
     * @ORM\Column(name="frontend_type", type="string", length=100, nullable=true)
     * @var string|null The frontend adapter (icecast,shoutcast,remote,etc)
     */
    protected $frontend_type;

    /**
     * @ORM\Column(name="frontend_config", type="json_array", nullable=true)
     * @var array|null An array containing station-specific frontend configuration
     */
    protected $frontend_config;

    /**
     * @ORM\Column(name="backend_type", type="string", length=100, nullable=true)
     * @var string|null The backend adapter (liquidsoap,etc)
     */
    protected $backend_type;

    /**
     * @ORM\Column(name="backend_config", type="json_array", nullable=true)
     * @var array|null An array containing station-specific backend configuration
     */
    protected $backend_config;

    /**
     * @ORM\Column(name="adapter_api_key", type="string", length=150, nullable=true)
     * @var string|null An internal-use API key used for container-to-container communications from Liquidsoap to AzuraCast
     */
    protected $adapter_api_key;

    /**
     * @ORM\Column(name="description", type="text", nullable=true)
     * @var string|null
     */
    protected $description;

    /**
     * @ORM\Column(name="url", type="string", length=255, nullable=true)
     * @var string|null
     */
    protected $url;

    /**
     * @ORM\Column(name="genre", type="string", length=150, nullable=true)
     * @var string|null
     */
    protected $genre;

    /**
     * @ORM\Column(name="radio_base_dir", type="string", length=255, nullable=true)
     * @var string|null
     */
    protected $radio_base_dir;

    /**
     * @ORM\Column(name="radio_media_dir", type="string", length=255, nullable=true)
     * @var string|null
     */
    protected $radio_media_dir;

    /**
     * @ORM\Column(name="nowplaying", type="array", nullable=true)
     * @var mixed|null
     */
    protected $nowplaying;

    /**
     * @ORM\Column(name="nowplaying_timestamp", type="integer", nullable=true)
     * @var int
     */
    protected $nowplaying_timestamp;

    /**
     * @ORM\Column(name="automation_settings", type="json_array", nullable=true)
     * @var array|null
     */
    protected $automation_settings;

    /**
     * @ORM\Column(name="automation_timestamp", type="integer", nullable=true)
     * @var int|null The UNIX timestamp when station automation was most recently run.
     */
    protected $automation_timestamp = 0;

    /**
     * @ORM\Column(name="enable_requests", type="boolean", nullable=false)
     * @var bool Whether listeners can request songs to play on this station.
     */
    protected $enable_requests = false;

    /**
     * @ORM\Column(name="request_delay", type="integer", nullable=true)
     * @var int|null
     */
    protected $request_delay = self::DEFAULT_REQUEST_DELAY;

    /**
     * @ORM\Column(name="request_threshold", type="integer", nullable=true)
     * @var int|null
     */
    protected $request_threshold = self::DEFAULT_REQUEST_THRESHOLD;

    /**
     * @ORM\Column(name="disconnect_deactivate_streamer", type="integer", nullable=true, options={"default":0})
     * @var int
     */
    protected $disconnect_deactivate_streamer = self::DEFAULT_DISCONNECT_DEACTIVATE_STREAMER;

    /**
     * @ORM\Column(name="enable_streamers", type="boolean", nullable=false)
     * @var bool Whether streamers are allowed to broadcast to this station at all.
     */
    protected $enable_streamers = false;

    /**
     * @ORM\Column(name="is_streamer_live", type="boolean", nullable=false)
     * @var bool Whether a streamer is currently active on the station.
     */
    protected $is_streamer_live = false;

    /**
     * @ORM\Column(name="enable_public_page", type="boolean", nullable=false)
     * @var bool Whether this station is visible as a public page and in a now-playing API response.
     */
    protected $enable_public_page = true;

    /**
     * @ORM\Column(name="needs_restart", type="boolean")
     * @var bool Whether to show the "Restart station to apply changes" sidebar for this station
     */
    protected $needs_restart = false;

    /**
     * @ORM\Column(name="has_started", type="boolean")
     * @var bool
     */
    protected $has_started = false;

    /**
     * @ORM\Column(name="api_history_items", type="smallint")
     * @var int|null The number of "last played" history items to show for a given station in the Now Playing API responses.
     */
    protected $api_history_items = self::DEFAULT_API_HISTORY_ITEMS;

    /**
     * @ORM\Column(name="storage_quota", type="bigint", nullable=true)
     * @var string|null
     */
    protected $storage_quota;

    /**
     * @ORM\Column(name="storage_used", type="bigint", nullable=true)
     * @var string|null
     */
    protected $storage_used;

    /**
     * @ORM\OneToMany(targetEntity="SongHistory", mappedBy="station")
     * @ORM\OrderBy({"timestamp" = "DESC"})
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
     * @ORM\OneToMany(targetEntity="StationWebhook", mappedBy="station", fetch="EXTRA_LAZY")
     * @var Collection
     */
    protected $webhooks;

    public function __construct()
    {
        $this->history = new ArrayCollection;
        $this->media = new ArrayCollection;
        $this->playlists = new ArrayCollection;
        $this->mounts = new ArrayCollection;
        $this->remotes = new ArrayCollection;
        $this->webhooks = new ArrayCollection;
        $this->streamers = new ArrayCollection;
    }

    /**
     * @return int
     */
    public function getId(): int
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

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->is_enabled;
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
     * @param $frontend_config
     * @param bool $force_overwrite
     */
    public function setFrontendConfig($frontend_config, $force_overwrite = false): void
    {
        $config = ($force_overwrite) ? [] : (array)$this->frontend_config;
        foreach((array)$frontend_config as $cfg_key => $cfg_val) {
            $config[$cfg_key] = $cfg_val;
        }

        if ($this->frontend_config !== $config) {
            $this->setNeedsRestart(true);
        }

        $this->frontend_config = $config;
    }

    /**
     * Set frontend configuration but do not overwrite existing values.
     *
     * @param $default_config
     */
    public function setFrontendConfigDefaults($default_config): void
    {
        $frontend_config = (array)$this->frontend_config;

        foreach($default_config as $config_key => $config_value) {
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
     * @return array|null
     */
    public function getBackendConfig(): ?array
    {
        return $this->backend_config;
    }

    /**
     * @param $backend_config
     * @param bool $force_overwrite
     */
    public function setBackendConfig($backend_config, $force_overwrite = false): void
    {
        $config = ($force_overwrite) ? [] : (array)$this->backend_config;
        foreach((array)$backend_config as $cfg_key => $cfg_val) {
            $config[$cfg_key] = $cfg_val;
        }

        if ($this->backend_config !== $config) {
            $this->setNeedsRestart(true);
        }

        $this->backend_config = $config;
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
     * @param $api_key
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
     * @param $new_dir
     */
    public function setRadioBaseDir($new_dir): void
    {
        $new_dir = $this->_truncateString(trim($new_dir));

        if (strcmp($this->radio_base_dir, $new_dir) !== 0) {
            $this->radio_base_dir = $new_dir;

            $radio_dirs = [
                $this->radio_base_dir,
                $this->getRadioMediaDir(),
                $this->getRadioAlbumArtDir(),
                $this->getRadioPlaylistsDir(),
                $this->getRadioConfigDir(),
                $this->getRadioTempDir(),
            ];
            foreach ($radio_dirs as $radio_dir) {
                if (!file_exists($radio_dir)) {
                    if (!mkdir($radio_dir, 0777) && !is_dir($radio_dir)) {
                        throw new \RuntimeException(sprintf('Directory "%s" was not created', $radio_dir));
                    }
                }
            }
        }
    }

    /**
     * @return string
     */
    public function getRadioMediaDir(): string
    {
        return (!empty($this->radio_media_dir))
            ? $this->radio_media_dir
            : $this->radio_base_dir.'/media';
    }

    /**
     * @return string
     */
    public function getRadioAlbumArtDir(): string
    {
        return $this->radio_base_dir.'/album_art';
    }

    /**
     * @return string
     */
    public function getRadioTempDir(): string
    {
        return $this->radio_base_dir.'/temp';
    }

    /**
     * Given an absolute path, return a path relative to this station's media directory.
     *
     * @param $full_path
     * @return string
     */
    public function getRelativeMediaPath($full_path): string
    {
        return ltrim(str_replace($this->getRadioMediaDir(), '', $full_path), '/');
    }

    /**
     * @return string
     */
    public function getRadioPlaylistsDir(): string
    {
        return $this->radio_base_dir.'/playlists';
    }

    /**
     * @return string
     */
    public function getRadioConfigDir(): string
    {
        return $this->radio_base_dir.'/config';
    }

    /**
     * @param $new_dir
     */
    public function setRadioMediaDir(?string $new_dir): void
    {
        $new_dir = $this->_truncateString(trim($new_dir));

        if ($new_dir && $new_dir !== $this->radio_media_dir) {
            if (!empty($new_dir) && !file_exists($new_dir)) {
                if (!mkdir($new_dir, 0777, true) && !is_dir($new_dir)) {
                    throw new \RuntimeException(sprintf('Directory "%s" was not created', $new_dir));
                }
            }

            $this->radio_media_dir = $new_dir;
        }
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
        $raw_quota = $this->getRawStorageQuota();

        return ($raw_quota instanceof BigInteger)
            ? Quota::getReadableSize($raw_quota)
            : '';
    }

    /**
     * @return BigInteger|null
     */
    public function getRawStorageQuota(): ?BigInteger
    {
        $size = $this->storage_quota;

        return (null !== $size)
            ? BigInteger::of($size)
            : null;
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
     * @return string|null
     */
    public function getStorageUsed(): ?string
    {
        $raw_size = $this->getRawStorageUsed();

        return ($raw_size instanceof BigInteger)
            ? Quota::getReadableSize($raw_size)
            : '';
    }

    /**
     * @return BigInteger|null
     */
    public function getRawStorageUsed(): ?BigInteger
    {
        $size = $this->storage_used;

        if (null === $size) {
            $total_size = disk_total_space($this->getRadioMediaDir());
            $used_size = disk_free_space($this->getRadioMediaDir());

            return BigInteger::of($total_size)
                ->minus($used_size)
                ->abs();
        }

        return BigInteger::of($size);
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
     * Increment the current used storage total.
     *
     * @param BigInteger|string|int $new_storage_amount
     */
    public function addStorageUsed($new_storage_amount): void
    {
        $current_storage_used = $this->getRawStorageUsed();
        if (null === $current_storage_used) {
            return;
        }

        $this->storage_used = (string)$current_storage_used->plus($new_storage_amount);
    }

    /**
     * Decrement the current used storage total.
     *
     * @param BigInteger|string|int $amount_to_remove
     */
    public function removeStorageUsed($amount_to_remove): void
    {
        $current_storage_used = $this->getRawStorageUsed();
        if (null === $current_storage_used) {
            return;
        }

        $this->storage_used = (string)$current_storage_used->minus($amount_to_remove);
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
        $quota = $this->getRawStorageQuota();
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

        $used = $this->getRawStorageUsed();
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
        return Quota::getPercentage($this->getRawStorageUsed(), $this->getRawStorageAvailable());
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
     * @param $name
     * @return string
     */
    public static function getStationShortName($name): string
    {
        return strtolower(preg_replace("/[^A-Za-z0-9_]/", '', str_replace(' ', '_', trim($name))));
    }

    /**
     * Retrieve the API version of the object/array.
     *
     * @param AbstractFrontend $fa
     * @param AdapterProxy[] $remote_adapters
     * @param UriInterface|null $base_url
     * @return Api\Station
     */
    public function api(AbstractFrontend $fa, array $remote_adapters = [], UriInterface $base_url = null): Api\Station
    {
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
                $mounts[] = $mount->api($fa, $base_url);
            }
        }
        $response->mounts = $mounts;

        $remotes = [];
        foreach($remote_adapters as $ra_proxy) {
            $remotes[] = $ra_proxy->getRemote()->api($ra_proxy->getAdapter());
        }
        $response->remotes = $remotes;

        return $response;
    }
}
