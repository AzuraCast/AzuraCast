<?php
namespace Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Interop\Container\ContainerInterface;

/**
 * @Table(name="station")
 * @Entity(repositoryClass="Entity\Repository\StationRepository")
 * @HasLifecycleCallbacks
 */
class Station extends \App\Doctrine\Entity
{
    public function __construct()
    {
        $this->automation_timestamp = 0;
        $this->enable_streamers = false;
        $this->enable_requests = false;

        $this->request_delay = 5;
        $this->request_threshold = 15;

        $this->needs_restart = false;

        $this->history = new ArrayCollection;
        $this->managers = new ArrayCollection;

        $this->media = new ArrayCollection;
        $this->playlists = new ArrayCollection;
        $this->mounts = new ArrayCollection;

        $this->streamers = new ArrayCollection;
    }

    /**
     * @Column(name="id", type="integer")
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /** @Column(name="name", type="string", length=100, nullable=true) */
    protected $name;

    public function getShortName()
    {
        return self::getStationShortName($this->name);
    }

    /** @Column(name="frontend_type", type="string", length=100, nullable=true) */
    protected $frontend_type;

    /** @Column(name="frontend_config", type="json_array", nullable=true) */
    protected $frontend_config;

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

    /** @Column(name="backend_type", type="string", length=100, nullable=true) */
    protected $backend_type;

    /** @Column(name="backend_config", type="json_array", nullable=true) */
    protected $backend_config;

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

    /** @Column(name="description", type="text", nullable=true) */
    protected $description;

    /** @Column(name="url", type="string", length=255, nullable=true) */
    protected $url;

    /** @Column(name="radio_base_dir", type="string", length=255, nullable=true) */
    protected $radio_base_dir;

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

    /** @Column(name="radio_media_dir", type="string", length=255, nullable=true) */
    protected $radio_media_dir;

    public function setRadioMediaDir($new_dir)
    {
        if ($new_dir !== $this->radio_media_dir) {
            $new_dir = trim($new_dir);

            if (!empty($new_dir) && !file_exists($new_dir)) {
                mkdir($new_dir, 0777, true);
            }

            $this->radio_media_dir = $new_dir;
        }
    }

    public function getRadioMediaDir()
    {
        return (!empty($this->radio_media_dir))
            ? $this->radio_media_dir
            : $this->radio_base_dir.'/media';
    }

    public function getRadioPlaylistsDir()
    {
        return $this->radio_base_dir.'/playlists';
    }

    public function getRadioConfigDir()
    {
        return $this->radio_base_dir.'/config';
    }

    /** @Column(name="nowplaying_data", type="json_array", nullable=true) */
    protected $nowplaying_data;

    /** @Column(name="automation_settings", type="json_array", nullable=true) */
    protected $automation_settings;

    /** @Column(name="automation_timestamp", type="integer", nullable=true) */
    protected $automation_timestamp;

    /** @Column(name="enable_requests", type="boolean", nullable=false) */
    protected $enable_requests;

    /** @Column(name="request_delay", type="integer", nullable=true) */
    protected $request_delay;

    /** @Column(name="request_threshold", type="integer", nullable=true) */
    protected $request_threshold;

    /** @Column(name="enable_streamers", type="boolean", nullable=false) */
    protected $enable_streamers;

    /** @Column(name="needs_restart", type="boolean") */
    protected $needs_restart;

    /**
     * @OneToMany(targetEntity="SongHistory", mappedBy="station")
     * @OrderBy({"timestamp" = "DESC"})
     */
    protected $history;

    /**
     * @OneToMany(targetEntity="StationMedia", mappedBy="station")
     */
    protected $media;

    /**
     * @OneToMany(targetEntity="StationStreamer", mappedBy="station")
     */
    protected $streamers;

    /**
     * @OneToMany(targetEntity="RolePermission", mappedBy="station")
     */
    protected $permissions;

    /**
     * @OneToMany(targetEntity="StationPlaylist", mappedBy="station")
     * @OrderBy({"type" = "ASC","weight" = "DESC"})
     */
    protected $playlists;

    /**
     * @OneToMany(targetEntity="StationMount", mappedBy="station")
     */
    protected $mounts;

    /**
     * Write all configuration changes to the filesystem and reload supervisord.
     *
     * @param ContainerInterface $di
     */
    public function writeConfiguration(ContainerInterface $di)
    {
        if (APP_TESTING_MODE) {
            return;
        }

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
        list($frontend_group, $frontend_program) = explode(':', $frontend_name);

        // Write group section of config
        $programs = [];
        if ($backend->hasCommand()) {
            $programs[] = $backend_program;
        }
        if ($frontend->hasCommand()) {
            $programs[] = $frontend_program;
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
            $supervisor_config[] = '';
        }

        // Write backend
        if ($backend->hasCommand()) {
            $supervisor_config[] = '[program:' . $backend_program . ']';
            $supervisor_config[] = 'directory=' . $config_path;
            $supervisor_config[] = 'command=' . $backend->getCommand();
            $supervisor_config[] = 'user=azuracast';
            $supervisor_config[] = 'priority=100';
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
     * Static Functions
     */

    /**
     * @param $name
     * @return string
     */
    public static function getStationShortName($name)
    {
        return strtolower(preg_replace("/[^A-Za-z0-9_]/", '', str_replace(' ', '_', trim($name))));
    }

    /**
     * @param $name
     * @return mixed
     */
    public static function getStationClassName($name)
    {
        $name = preg_replace("/[^A-Za-z0-9_ ]/", '', $name);
        $name = str_replace('_', ' ', $name);
        $name = str_replace(' ', '', $name);

        return $name;
    }

    /**
     * @return array
     */
    public static function getFrontendAdapters()
    {
        $adapters = [
            'icecast' => [
                'name' => 'IceCast 2.4',
                'class' => '\AzuraCast\Radio\Frontend\IceCast',
            ],
            'shoutcast2' => [
                'name' => 'ShoutCast 2',
                'class' => '\AzuraCast\Radio\Frontend\ShoutCast2',
            ],
            'remote' => [
                'name' => _('External Radio Server (Statistics Only)'),
                'class' => '\AzuraCast\Radio\Frontend\Remote',
            ],
        ];

        $adapters = array_filter($adapters, function($adapter_info) {
            /** @var \AzuraCast\Radio\AdapterAbstract $adapter_class */
            $adapter_class = $adapter_info['class'];
            return $adapter_class::isInstalled();
        });

        return [
            'default' => 'icecast',
            'adapters' => $adapters,
        ];
    }

    /**
     * @return array
     */
    public static function getBackendAdapters()
    {
        $adapters = [
            'liquidsoap' => [
                'name' => 'LiquidSoap',
                'class' => '\AzuraCast\Radio\Backend\LiquidSoap',
            ],
            'none' => [
                'name' => _('Disabled'),
                'class' => '\AzuraCast\Radio\Backend\None',
            ],
        ];

        $adapters = array_filter($adapters, function($adapter_info) {
            /** @var \AzuraCast\Radio\AdapterAbstract $adapter_class */
            $adapter_class = $adapter_info['class'];
            return $adapter_class::isInstalled();
        });

        return [
            'default' => 'liquidsoap',
            'adapters' => $adapters,
        ];
    }

    /**
     * Retrieve the API version of the object/array.
     *
     * @param $row
     * @return array
     */
    public static function api(Station $row, ContainerInterface $di)
    {
        $fa = $row->getFrontendAdapter($di);

        $api = [
            'id' => (int)$row->id,
            'name' => (string)$row->name,
            'shortcode' => (string)self::getStationShortName($row['name']),
            'description' => (string)$row->description,
            'frontend' => (string)$row->frontend_type,
            'backend' => (string)$row->backend_type,
            'listen_url' => (string)$fa->getStreamUrl(),
            'mounts' => [],
        ];

        if ($row->mounts->count() > 0) {
            if ($fa->supportsMounts()) {
                foreach ($row->mounts as $mount_row) {
                    $api['mounts'][] = [
                        'name' => (string)$mount_row->name,
                        'is_default' => (bool)$mount_row->is_default,
                        'url' => (string)$fa->getUrlForMount($mount_row->name),
                    ];
                }
            }
        }

        return $api;
    }
}