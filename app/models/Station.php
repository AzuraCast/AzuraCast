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
    public function isEnablePublicPage(): bool
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
     * @return \AzuraCast\Radio\Frontend\FrontendAbstract
     * @throws \Exception
     */
    public function getFrontendAdapter(ContainerInterface $di)
    {
        $adapters = self::getFrontendAdapters();

        if (!isset($adapters['adapters'][$this->frontend_type])) {
            throw new \Exception('Adapter not found: ' . $this->frontend_type);
        }

        $class_name = $adapters['adapters'][$this->frontend_type]['class'];

        return new $class_name($di, $this);
    }

    /**
     * @return \AzuraCast\Radio\Backend\BackendAbstract
     * @throws \Exception
     */
    public function getBackendAdapter(ContainerInterface $di)
    {
        $adapters = self::getBackendAdapters();

        if (!isset($adapters['adapters'][$this->backend_type])) {
            throw new \Exception('Adapter not found: ' . $this->backend_type);
        }

        $class_name = $adapters['adapters'][$this->backend_type]['class'];

        return new $class_name($di, $this);
    }

    /**
     * Write all configuration changes to the filesystem and reload supervisord.
     *
     * @param ContainerInterface $di
     * @param bool $regen_auth_key Regenerate the API authorization key (will trigger a full reset of processes).
     */
    public function writeConfiguration(ContainerInterface $di, $regen_auth_key = false)
    {
        if (APP_TESTING_MODE) {
            return;
        }

        /** @var EntityManager $em */
        $em = $di['em'];

        if ($regen_auth_key || empty($this->getAdapterApiKey())) {
            $this->generateAdapterApiKey();
            $em->persist($this);
            $em->flush();
        }

        // Initialize adapters.
        $config_path = $this->getRadioConfigDir();
        $supervisor_config = [];
        $supervisor_config_path = $config_path . '/supervisord.conf';

        $frontend = $this->getFrontendAdapter($di);
        $backend = $this->getBackendAdapter($di);

        // If no processes need to be managed, remove any existing config.
        if (!$frontend->hasCommand() && !$backend->hasCommand()) {
            @unlink($supervisor_config_path);
            $this->_reloadSupervisor($di['supervisor']);
            return;
        }

        // Write config files for both backend and frontend.
        $frontend->write();
        $backend->write();

        // Get group information
        $backend_name = $backend->getProgramName();
        list($backend_group, $backend_program) = explode(':', $backend_name);

        $frontend_name = $frontend->getProgramName();
        list(,$frontend_program) = explode(':', $frontend_name);

        $frontend_watch_name = $frontend->getWatchProgramName();
        list(,$frontend_watch_program) = explode(':', $frontend_watch_name);

        // Write group section of config
        $programs = [];
        if ($backend->hasCommand()) {
            $programs[] = $backend_program;
        }
        if ($frontend->hasCommand()) {
            $programs[] = $frontend_program;
        }
        if ($frontend->hasWatchCommand()) {
            $programs[] = $frontend_watch_program;
        }

        $supervisor_config[] = '[group:' . $backend_group . ']';
        $supervisor_config[] = 'programs=' . implode(',', $programs);
        $supervisor_config[] = '';

        // Write frontend
        if ($frontend->hasCommand()) {
            $supervisor_config[] = '[program:' . $frontend_program . ']';
            $supervisor_config[] = 'directory=' . $config_path;
            $supervisor_config[] = 'command=' . $frontend->getCommand();
            $supervisor_config[] = 'user=azuracast';
            $supervisor_config[] = 'priority=90';

            if (APP_INSIDE_DOCKER) {
                $supervisor_config[] = 'stdout_logfile=/dev/stdout';
                $supervisor_config[] = 'stdout_logfile_maxbytes=0';
                $supervisor_config[] = 'stderr_logfile=/dev/stderr';
                $supervisor_config[] = 'stderr_logfile_maxbytes=0';
            }

            $supervisor_config[] = '';
        }

        // Write frontend watcher program
        if ($frontend->hasWatchCommand()) {
            $supervisor_config[] = '[program:' . $frontend_watch_program . ']';
            $supervisor_config[] = 'directory=/var/azuracast/servers/station-watcher';
            $supervisor_config[] = 'command=' . $frontend->getWatchCommand();
            $supervisor_config[] = 'user=azuracast';
            $supervisor_config[] = 'priority=95';

            if (APP_INSIDE_DOCKER) {
                $supervisor_config[] = 'stdout_logfile=/dev/stdout';
                $supervisor_config[] = 'stdout_logfile_maxbytes=0';
                $supervisor_config[] = 'stderr_logfile=/dev/stderr';
                $supervisor_config[] = 'stderr_logfile_maxbytes=0';
            }

            $supervisor_config[] = '';
        }

        // Write backend
        if ($backend->hasCommand()) {
            $supervisor_config[] = '[program:' . $backend_program . ']';
            $supervisor_config[] = 'directory=' . $config_path;
            $supervisor_config[] = 'command=' . $backend->getCommand();
            $supervisor_config[] = 'user=azuracast';
            $supervisor_config[] = 'priority=100';

            if (APP_INSIDE_DOCKER) {
                $supervisor_config[] = 'stdout_logfile=/dev/stdout';
                $supervisor_config[] = 'stdout_logfile_maxbytes=0';
                $supervisor_config[] = 'stderr_logfile=/dev/stderr';
                $supervisor_config[] = 'stderr_logfile_maxbytes=0';
            }

            $supervisor_config[] = '';
        }

        // Write config contents
        $supervisor_config_data = implode("\n", $supervisor_config);
        file_put_contents($supervisor_config_path, $supervisor_config_data);

        $this->_reloadSupervisor($di['supervisor']);
    }

    /**
     * Remove configuration (i.e. prior to station removal) and trigger a Supervisor refresh.
     * @param ContainerInterface $di
     */
    public function removeConfiguration(ContainerInterface $di)
    {
        if (APP_TESTING_MODE) {
            return;
        }

        $config_path = $this->getRadioConfigDir();
        $supervisor_config_path = $config_path . '/supervisord.conf';

        @unlink($supervisor_config_path);

        $this->_reloadSupervisor($di['supervisor']);
    }

    /**
     * Trigger a supervisord reload and restart all relevant services.
     * @param \Supervisor\Supervisor $supervisor
     */
    protected function _reloadSupervisor(\Supervisor\Supervisor $supervisor)
    {
        $reload_result = $supervisor->reloadConfig();

        $reload_added = $reload_result[0][0];
        $reload_changed = $reload_result[0][1];
        $reload_removed = $reload_result[0][2];

        foreach ($reload_removed as $group) {
            $supervisor->stopProcessGroup($group);
            $supervisor->removeProcessGroup($group);
        }

        foreach ($reload_changed as $group) {
            $supervisor->stopProcessGroup($group);
            $supervisor->removeProcessGroup($group);
            $supervisor->addProcessGroup($group);
        }

        foreach ($reload_added as $group) {
            $supervisor->addProcessGroup($group);
        }
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
     * @return array
     */
    public static function getFrontendAdapters(): array
    {
        static $adapters;

        if ($adapters === null) {
            $adapters = [
                'icecast' => [
                    'name' => sprintf(_('Use <b>%s</b> on this server'), 'Icecast 2.4'),
                    'class' => '\AzuraCast\Radio\Frontend\IceCast',
                ],
                'shoutcast2' => [
                    'name' => sprintf(_('Use <b>%s</b> on this server'), 'Shoutcast 2'),
                    'class' => '\AzuraCast\Radio\Frontend\ShoutCast2',
                ],
                'remote' => [
                    'name' => _('Connect to a <b>remote radio server</b>'),
                    'class' => '\AzuraCast\Radio\Frontend\Remote',
                ],
            ];

            $adapters = array_filter($adapters, function($adapter_info) {
                /** @var \AzuraCast\Radio\AdapterAbstract $adapter_class */
                $adapter_class = $adapter_info['class'];
                return $adapter_class::isInstalled();
            });
        }

        return [
            'default' => 'icecast',
            'adapters' => $adapters,
        ];
    }

    /**
     * @return array
     */
    public static function getBackendAdapters(): array
    {
        static $adapters;

        if ($adapters === null) {
            $adapters = [
                'liquidsoap' => [
                    'name' => sprintf(_('Use <b>%s</b> on this server'), 'LiquidSoap'),
                    'class' => '\AzuraCast\Radio\Backend\LiquidSoap',
                ],
                'none' => [
                    'name' => _('<b>Do not use</b> an AutoDJ service'),
                    'class' => '\AzuraCast\Radio\Backend\None',
                ],
            ];

            $adapters = array_filter($adapters, function ($adapter_info) {
                /** @var \AzuraCast\Radio\AdapterAbstract $adapter_class */
                $adapter_class = $adapter_info['class'];
                return $adapter_class::isInstalled();
            });
        }

        return [
            'default' => 'liquidsoap',
            'adapters' => $adapters,
        ];
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