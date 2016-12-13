<?php
namespace Entity;

use \Doctrine\Common\Collections\ArrayCollection;
use Interop\Container\ContainerInterface;

/**
 * @Table(name="station")
 * @Entity(repositoryClass="StationRepository")
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

    /** @Column(name="frontend_config", type="json", nullable=true) */
    protected $frontend_config;

    /**
     * @return \App\Radio\Frontend\FrontendAbstract
     * @throws \Exception
     */
    public function getFrontendAdapter(ContainerInterface $di)
    {
        $adapters = self::getFrontendAdapters();

        if (!isset($adapters['adapters'][$this->frontend_type]))
            throw new \Exception('Adapter not found: '.$this->frontend_type);

        $class_name = $adapters['adapters'][$this->frontend_type]['class'];
        return new $class_name($di, $this);
    }

    /** @Column(name="backend_type", type="string", length=100, nullable=true) */
    protected $backend_type;

    /** @Column(name="backend_config", type="json", nullable=true) */
    protected $backend_config;

    /**
     * @return \App\Radio\Backend\BackendAbstract
     * @throws \Exception
     */
    public function getBackendAdapter(ContainerInterface $di)
    {
        $adapters = self::getBackendAdapters();

        if (!isset($adapters['adapters'][$this->backend_type]))
            throw new \Exception('Adapter not found: '.$this->backend_type);

        $class_name = $adapters['adapters'][$this->backend_type]['class'];
        return new $class_name($di, $this);
    }

    /** @Column(name="description", type="text", nullable=true) */
    protected $description;

    /** @Column(name="radio_base_dir", type="string", length=255, nullable=true) */
    protected $radio_base_dir;

    public function setRadioBaseDir($new_dir)
    {
        if (strcmp($this->radio_base_dir, $new_dir) !== 0)
        {
            $this->radio_base_dir = $new_dir;

            $radio_dirs = [$this->radio_base_dir, $this->getRadioMediaDir(), $this->getRadioPlaylistsDir(), $this->getRadioConfigDir()];
            foreach($radio_dirs as $radio_dir)
            {
                if (!file_exists($radio_dir))
                    mkdir($radio_dir, 0777);
            }
        }
    }

    public function getRadioMediaDir()
    {
        return $this->radio_base_dir.'/media';
    }

    public function getRadioPlaylistsDir()
    {
        return $this->radio_base_dir.'/playlists';
    }

    public function getRadioConfigDir()
    {
        return $this->radio_base_dir.'/config';
    }

    /** @Column(name="nowplaying_data", type="json", nullable=true) */
    protected $nowplaying_data;

    /** @Column(name="automation_settings", type="json", nullable=true) */
    protected $automation_settings;

    /** @Column(name="automation_timestamp", type="integer", nullable=true) */
    protected $automation_timestamp;

    /** @Column(name="enable_requests", type="boolean", nullable=false) */
    protected $enable_requests;

    /** @Column(name="request_delay", type="integer", nullable=true) */
    protected $request_delay;

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
        if (APP_TESTING_MODE)
            return;

        /** @var \Supervisor\Supervisor */
        $supervisor = $di['supervisor'];

        $config_path = $this->getRadioConfigDir();
        $supervisor_config = [];
        $supervisor_config_path = $config_path.'/supervisord.conf';

        $frontend = $this->getFrontendAdapter($di);
        $backend = $this->getBackendAdapter($di);

        // If no processes need to be managed, remove any existing config.
        if (!$frontend->hasCommand() && !$backend->hasCommand())
        {
            @unlink($supervisor_config_path);
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
        if ($backend->hasCommand())
            $programs[] = $backend_program;
        if ($frontend->hasCommand())
            $programs[] = $frontend_program;

        $supervisor_config[] = '[group:'.$backend_group.']';
        $supervisor_config[] = 'programs='.implode(',', $programs);
        $supervisor_config[] = '';

        // Write frontend
        if ($frontend->hasCommand())
        {
            $supervisor_config[] = '[program:'.$frontend_program.']';
            $supervisor_config[] = 'directory='.$config_path;
            $supervisor_config[] = 'command='.$frontend->getCommand();
            $supervisor_config[] = 'user=azuracast';
            $supervisor_config[] = 'priority=90';
            $supervisor_config[] = '';
        }

        // Write backend
        if ($backend->hasCommand())
        {
            $supervisor_config[] = '[program:'.$backend_program.']';
            $supervisor_config[] = 'directory='.$config_path;
            $supervisor_config[] = 'command='.$backend->getCommand();
            $supervisor_config[] = 'user=azuracast';
            $supervisor_config[] = 'priority=100';
            $supervisor_config[] = '';
        }

        // Write config contents
        $supervisor_config_data = implode("\n", $supervisor_config);
        file_put_contents($supervisor_config_path, $supervisor_config_data);

        // Trigger a supervisord reload and restart all relevant services.
        $reload_result = $supervisor->reloadConfig();

        $reload_added = $reload_result[0][0];
        $reload_changed = $reload_result[0][1];
        $reload_removed = $reload_result[0][2];

        foreach($reload_removed as $group)
        {
            $supervisor->stopProcessGroup($group);
            $supervisor->removeProcessGroup($group);
        }

        foreach($reload_changed as $group)
        {
            $supervisor->stopProcessGroup($group);
            $supervisor->removeProcessGroup($group);
            $supervisor->addProcessGroup($group);
        }

        foreach($reload_added as $group)
        {
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
        return [
            'default' => 'icecast',
            'adapters' => [
                'icecast' => [
                    'name' => 'IceCast 2.4',
                    'class' => '\App\Radio\Frontend\IceCast',
                ],
                'shoutcast2' => array(
                    'name'      => 'ShoutCast 2',
                    'class'     => '\App\Radio\Frontend\ShoutCast2',
                ),
                'remote' => [
                    'name' => _('External Radio Server (Statistics Only)'),
                    'class' => '\App\Radio\Frontend\Remote',
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    public static function getBackendAdapters()
    {
        return [
            'default' => 'liquidsoap',
            'adapters' => [
                'liquidsoap' => [
                    'name' => 'LiquidSoap',
                    'class' => '\App\Radio\Backend\LiquidSoap',
                ],
                'none' => [
                    'name' => _('Disabled'),
                    'class' => '\App\Radio\Backend\None',
                ],
            ],
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
            'id'        => (int)$row->id,
            'name'      => $row->name,
            'shortcode' => self::getStationShortName($row['name']),
            'description' => $row->description,
            'frontend'  => $row->frontend_type,
            'backend'   => $row->backend_type,
            'listen_url' => $fa->getStreamUrl(),
            'mounts'    => [],
        ];

        if ($row->mounts->count() > 0)
        {
            if ($fa->supportsMounts())
            {
                foreach($row->mounts as $mount_row)
                {
                    $api['mounts'][] = [
                        'name'  => $mount_row->name,
                        'is_default' => (bool)$mount_row->is_default,
                        'url'   => $fa->getUrlForMount($mount_row->name),
                    ];
                }
            }
        }

        return $api;
    }
}

use App\Doctrine\Repository;

class StationRepository extends Repository
{
    /**
     * @return mixed
     */
    public function fetchAll()
    {
        return $this->_em->createQuery('SELECT s FROM '.$this->_entityName.' s ORDER BY s.name ASC')
            ->execute();
    }

    /**
     * @param bool $cached
     * @param null $order_by
     * @param string $order_dir
     * @return array
     */
    public function fetchArray($cached = true, $order_by = NULL, $order_dir = 'ASC')
    {
        $stations = parent::fetchArray($cached, $order_by, $order_dir);
        foreach($stations as &$station)
            $station['short_name'] = Station::getStationShortName($station['name']);

        return $stations;
    }

    /**
     * @param bool $add_blank
     * @param \Closure|NULL $display
     * @param string $pk
     * @param string $order_by
     * @return array
     */
    public function fetchSelect($add_blank = FALSE, \Closure $display = NULL, $pk = 'id', $order_by = 'name')
    {
        $select = array();

        // Specify custom text in the $add_blank parameter to override.
        if ($add_blank !== FALSE)
            $select[''] = ($add_blank === TRUE) ? 'Select...' : $add_blank;

        // Build query for records.
        $results = $this->fetchArray();

        // Assemble select values and, if necessary, call $display callback.
        foreach((array)$results as $result)
        {
            $key = $result[$pk];
            $value = ($display === NULL) ? $result['name'] : $display($result);
            $select[$key] = $value;
        }

        return $select;
    }

    /**
     * @param bool $cached
     * @return array
     */
    public function getShortNameLookup($cached = true)
    {
        $stations = $this->fetchArray($cached);

        $lookup = array();
        foreach ($stations as $station)
            $lookup[$station['short_name']] = $station;

        return $lookup;
    }

    /**
     * @param $short_code
     * @return null|object
     */
    public function findByShortCode($short_code)
    {
        $short_names = $this->getShortNameLookup();

        if (isset($short_names[$short_code]))
        {
            $id = $short_names[$short_code]['id'];
            return $this->find($id);
        }

        return NULL;
    }

    /**
     * Create a station based on the specified data.
     *
     * @param $data
     * @param ContainerInterface $di
     * @return Station
     */
    public function create($data, ContainerInterface $di)
    {
        $station = new Station;
        $station->fromArray($this->_em, $data);

        // Create path for station.
        $station_base_dir = realpath(APP_INCLUDE_ROOT.'/..').'/stations';

        $station_dir = $station_base_dir.'/'.$station->getShortName();
        $station->setRadioBaseDir($station_dir);

        $this->_em->persist($station);

        // Generate station ID.
        $this->_em->flush();

        // Scan directory for any existing files.
        $media_sync = new \App\Sync\Media($di);

        set_time_limit(600);
        $media_sync->importMusic($station);
        $this->_em->refresh($station);

        $media_sync->importPlaylists($station);
        $this->_em->refresh($station);

        // Load adapters.
        $frontend_adapter = $station->getFrontendAdapter($di);
        $backend_adapter = $station->getBackendAdapter($di);

        // Create default mountpoints if station supports them.
        if ($frontend_adapter->supportsMounts())
        {
            // Create default mount points.
            $mount_points = $frontend_adapter->getDefaultMounts();

            foreach($mount_points as $mount_point)
            {
                $mount_point['station'] = $station;
                $mount_record = new StationMount;
                $mount_record->fromArray($this->_em, $mount_point);

                $this->_em->persist($mount_record);
            }

            $this->_em->flush();
            $this->_em->refresh($station);
        }

        // Load configuration from adapter to pull source and admin PWs.
        $frontend_adapter->read();

        // Write the adapter configurations and update supervisord.
        $station->writeConfiguration($di);

        // Save changes and continue to the last setup step.
        $this->_em->persist($station);
        $this->_em->flush();

        return $station;
    }

    /**
     * @param Station $station
     * @param ContainerInterface $di
     */
    public function destroy(Station $station, ContainerInterface $di)
    {
        $frontend = $station->getFrontendAdapter($di);
        $backend = $station->getBackendAdapter($di);

        if ($frontend->hasCommand() || $backend->hasCommand())
        {
            /** @var \Supervisor\Supervisor */
            $supervisor = $di['supervisor'];

            $frontend_name = $frontend->getProgramName();
            list($frontend_group, $frontend_program) = explode(':', $frontend_name);

            $supervisor->stopProcessGroup($frontend_group);
        }

        // Remove media folders.
        $radio_dir = $station->getRadioBaseDir();
        \App\Utilities::rmdir_recursive($radio_dir);

        // Save changes and continue to the last setup step.
        $this->_em->remove($station);
        $this->_em->flush();
    }
}