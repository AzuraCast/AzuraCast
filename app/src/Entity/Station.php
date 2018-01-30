<?php
namespace Entity;

use AzuraCast\Radio\Frontend\FrontendAbstract;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManager;
use Interop\Container\ContainerInterface;

/**
 * @Table(name="station")
 * @Entity(repositoryClass="Entity\Repository\StationRepository")
 * @HasLifecycleCallbacks
 */
class Station
{
    /**
     * @Column(name="id", type="integer")
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     * @var int
     */
    protected $id;

    /**
     * @Column(name="name", type="string", length=100, nullable=true)
     * @var string|null The full display name of the station.
     */
    protected $name;

    /**
     * @Column(name="short_name", type="string", length=100, nullable=true)
     * @var string|null The URL-friendly name for the station, typically auto-generated from the full station name.
     */
    protected $short_name;

    /**
     * @Column(name="frontend_type", type="string", length=100, nullable=true)
     * @var string|null
     */
    protected $frontend_type;

    /**
     * @Column(name="frontend_config", type="json_array", nullable=true)
     * @var array|null
     */
    protected $frontend_config;

    /**
     * @Column(name="backend_type", type="string", length=100, nullable=true)
     * @var string|null
     */
    protected $backend_type;

    /**
     * @Column(name="backend_config", type="json_array", nullable=true)
     * @var array|null
     */
    protected $backend_config;

    /**
     * @Column(name="adapter_api_key", type="string", length=150, nullable=true)
     * @var string|null
     */
    protected $adapter_api_key;

    /**
     * @Column(name="description", type="text", nullable=true)
     * @var string|null
     */
    protected $description;

    /**
     * @Column(name="url", type="string", length=255, nullable=true)
     * @var string|null
     */
    protected $url;

    /**
     * @Column(name="radio_base_dir", type="string", length=255, nullable=true)
     * @var string|null
     */
    protected $radio_base_dir;

    /**
     * @Column(name="radio_media_dir", type="string", length=255, nullable=true)
     * @var string|null
     */
    protected $radio_media_dir;

    /**
     * @Column(name="nowplaying", type="array", nullable=true)
     * @var mixed|null
     */
    protected $nowplaying;

    /**
     * @Column(name="nowplaying_timestamp", type="integer", nullable=true)
     * @var int
     */
    protected $nowplaying_timestamp;

    /**
     * @Column(name="automation_settings", type="json_array", nullable=true)
     * @var array|null
     */
    protected $automation_settings;

    /**
     * @Column(name="automation_timestamp", type="integer", nullable=true)
     * @var int|null
     */
    protected $automation_timestamp;

    /**
     * @Column(name="enable_requests", type="boolean", nullable=false)
     * @var bool
     */
    protected $enable_requests;

    /**
     * @Column(name="request_delay", type="integer", nullable=true)
     * @var int|null
     */
    protected $request_delay;

    /**
     * @Column(name="request_threshold", type="integer", nullable=true)
     * @var int|null
     */
    protected $request_threshold;

    /**
     * @Column(name="enable_streamers", type="boolean", nullable=false)
     * @var bool
     */
    protected $enable_streamers;

    /**
     * @Column(name="enable_public_page", type="boolean", nullable=false)
     * @var bool
     */
    protected $enable_public_page;

    /**
     * @Column(name="needs_restart", type="boolean")
     * @var bool
     */
    protected $needs_restart;

    /**
     * @Column(name="has_started", type="boolean")
     * @var bool
     */
    protected $has_started;

    /**
     * @OneToMany(targetEntity="SongHistory", mappedBy="station")
     * @OrderBy({"timestamp" = "DESC"})
     * @var Collection
     */
    protected $history;

    /**
     * @OneToMany(targetEntity="StationMedia", mappedBy="station")
     * @var Collection
     */
    protected $media;

    /**
     * @OneToMany(targetEntity="StationStreamer", mappedBy="station")
     * @var Collection
     */
    protected $streamers;

    /**
     * @OneToMany(targetEntity="RolePermission", mappedBy="station")
     * @var Collection
     */
    protected $permissions;

    /**
     * @OneToMany(targetEntity="StationPlaylist", mappedBy="station")
     * @OrderBy({"type" = "ASC","weight" = "DESC"})
     * @var Collection
     */
    protected $playlists;

    /**
     * @OneToMany(targetEntity="StationMount", mappedBy="station")
     * @var Collection
     */
    protected $mounts;

    public function __construct()
    {
        $this->automation_timestamp = 0;
        $this->enable_streamers = false;
        $this->enable_requests = false;

        $this->request_delay = 5;
        $this->request_threshold = 15;

        $this->needs_restart = false;
        $this->has_started = false;
        $this->enable_public_page = true;

        $this->history = new ArrayCollection;
        $this->media = new ArrayCollection;
        $this->playlists = new ArrayCollection;
        $this->mounts = new ArrayCollection;
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
    public function setName(string $name = null)
    {
        $this->name = $name;

        if (empty($this->short_name) && !empty($name)) {
            $this->short_name = self::getStationShortName($name);
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
            $this->short_name = $short_name;
        }
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
    public function setFrontendType(string $frontend_type = null)
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
    public function setFrontendConfig($frontend_config, $force_overwrite = false)
    {
        $config = ($force_overwrite) ? [] : (array)$this->frontend_config;
        foreach((array)$frontend_config as $cfg_key => $cfg_val) {
            $config[$cfg_key] = $cfg_val;
        }
        $this->frontend_config = $config;
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
    public function setBackendType(string $backend_type = null)
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
    public function setBackendConfig($backend_config, $force_overwrite = false)
    {
        $config = ($force_overwrite) ? [] : (array)$this->backend_config;
        foreach((array)$backend_config as $cfg_key => $cfg_val) {
            $config[$cfg_key] = $cfg_val;
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
    public function generateAdapterApiKey()
    {
        $this->adapter_api_key = bin2hex(random_bytes(50));
    }

    /**
     * Authenticate the supplied adapter API key.
     *
     * @param $api_key
     * @return bool
     */
    public function validateAdapterApiKey($api_key)
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
    public function setDescription(string $description = null)
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
    public function setUrl(string $url = null)
    {
        $this->url = $url;
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
    public function setRadioBaseDir($new_dir)
    {
        if (strcmp($this->radio_base_dir, $new_dir) !== 0) {
            $this->radio_base_dir = $new_dir;

            $radio_dirs = [
                $this->radio_base_dir,
                $this->getRadioMediaDir(),
                $this->getRadioPlaylistsDir(),
                $this->getRadioConfigDir()
            ];
            foreach ($radio_dirs as $radio_dir) {
                if (!file_exists($radio_dir)) {
                    mkdir($radio_dir, 0777);
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
    public function setRadioMediaDir(string $new_dir)
    {
        if ($new_dir !== $this->radio_media_dir) {
            $new_dir = trim($new_dir);

            if (!empty($new_dir) && !file_exists($new_dir)) {
                mkdir($new_dir, 0777, true);
            }

            $this->radio_media_dir = $new_dir;
        }
    }

    /**
     * @return mixed|null
     */
    public function getNowplaying()
    {
        return $this->nowplaying;
    }

    /**
     * @param mixed|null $nowplaying
     */
    public function setNowplaying($nowplaying = null)
    {
        $this->nowplaying = $nowplaying;
        $this->nowplaying_timestamp = time();
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
    public function setAutomationSettings(array $automation_settings = null)
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
    public function setAutomationTimestamp(int $automation_timestamp = null)
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
    public function setEnableRequests(bool $enable_requests)
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
    public function setRequestDelay(int $request_delay = null)
    {
        $this->request_delay = $request_delay;
    }

    /**
     * @return int|null
     */
    public function getRequestThreshold()
    {
        return $this->request_threshold;
    }

    /**
     * @param int|null $request_threshold
     */
    public function setRequestThreshold(int $request_threshold = null)
    {
        $this->request_threshold = $request_threshold;
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
    public function setEnableStreamers(bool $enable_streamers)
    {
        $this->enable_streamers = $enable_streamers;
    }

    /**
     * @return bool
     */
    public function getEnablePublicPage(): bool
    {
        return (bool)$this->enable_public_page;
    }

    /**
     * @param bool $enable_public_page
     */
    public function setEnablePublicPage(bool $enable_public_page)
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
    public function setNeedsRestart(bool $needs_restart)
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
    public function setHasStarted(bool $has_started)
    {
        $this->has_started = $has_started;
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
     * @param $name
     * @return string
     */
    public static function getStationShortName($name): string
    {
        return strtolower(preg_replace("/[^A-Za-z0-9_]/", '', str_replace(' ', '_', trim($name))));
    }

    /**
     * @param $name
     * @return string
     */
    public static function getStationClassName($name): string
    {
        $name = preg_replace("/[^A-Za-z0-9_ ]/", '', $name);
        $name = str_replace('_', ' ', $name);
        $name = str_replace(' ', '', $name);

        return $name;
    }

    /**
     * Retrieve the API version of the object/array.
     *
     * @param FrontendAbstract $fa
     * @return Api\Station
     */
    public function api(FrontendAbstract $fa): Api\Station
    {
        $response = new Api\Station;
        $response->id = (int)$this->id;
        $response->name = (string)$this->name;
        $response->shortcode = (string)$this->getShortName();
        $response->description = (string)$this->description;
        $response->frontend = (string)$this->frontend_type;
        $response->backend = (string)$this->backend_type;
        $response->listen_url = (string)$fa->getStreamUrl();
        $response->is_public = (bool)$this->enable_public_page;

        $mounts = [];
        if ($fa->supportsMounts() && $this->mounts->count() > 0) {
            foreach ($this->mounts as $mount) {
                $mounts[] = $mount->api($fa);
            }
        }

        $response->mounts = $mounts;
        return $response;
    }
}