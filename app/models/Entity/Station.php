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

        $this->history = new ArrayCollection;
        $this->managers = new ArrayCollection;

        $this->media = new ArrayCollection;
        $this->playlists = new ArrayCollection;

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
     * @return \App\Radio\Frontend\AdapterAbstract
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
     * @return \App\Radio\Backend\AdapterAbstract
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

    /**
     * @OneToMany(targetEntity="SongHistory", mappedBy="station")
     * @OrderBy({"timestamp" = "DESC"})
     */
    protected $history;

    /**
     * @ManyToMany(targetEntity="User", mappedBy="stations")
     */
    protected $managers;

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
        return array(
            'default' => 'icecast',
            'adapters' => array(
                'icecast' => array(
                    'name'      => 'IceCast v2.4 or Above',
                    'class'     => '\App\Radio\Frontend\IceCast',
                ),
                /*
                'shoutcast1' => array(
                    'name'      => 'ShoutCast 1',
                    'class'     => '\App\Radio\Frontend\ShoutCast1',
                ),
                'shoutcast2' => array(
                    'name'      => 'ShoutCast 2',
                    'class'     => '\App\Radio\Frontend\ShoutCast2',
                ),
                */
            ),
        );
    }

    /**
     * @return array
     */
    public static function getBackendAdapters()
    {
        return array(
            'default' => 'liquidsoap',
            'adapters' => array(
                'liquidsoap' => array(
                    'name'      => 'LiquidSoap',
                    'class'     => '\App\Radio\Backend\LiquidSoap',
                ),
            ),
        );
    }

    /**
     * Retrieve the API version of the object/array.
     *
     * @param $row
     * @return array
     */
    public static function api($row)
    {
        $api = array(
            'id'        => (int)$row['id'],
            'name'      => $row['name'],
            'shortcode' => self::getStationShortName($row['name']),
        );

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
     * @param $data
     */
    public function create($data, ContainerInterface $di)
    {
        $station = new Station;
        $station->fromArray($this->_em, $data);

        // Create path for station.
        $station_base_dir = realpath(APP_INCLUDE_ROOT.'/..').'/stations';

        $station_dir = $station_base_dir.'/'.$station->getShortName();
        $station->setRadioBaseDir($station_dir);

        // Generate station ID.
        $this->_em->persist($station);
        $this->_em->flush();

        // Scan directory for any existing files.
        $media_sync = new \App\Sync\Media($di);

        set_time_limit(600);
        $media_sync->importMusic($station);
        $this->_em->refresh($station);

        $media_sync->importPlaylists($station);
        $this->_em->refresh($station);

        // Load configuration from adapter to pull source and admin PWs.
        $frontend_adapter = $station->getFrontendAdapter($di);
        $frontend_adapter->read();

        // Write initial XML file (if it doesn't exist).
        $frontend_adapter->write();
        $frontend_adapter->restart();

        // Write an empty placeholder configuration.
        $backend_adapter = $station->getBackendAdapter($di);
        $backend_adapter->write();
        $backend_adapter->restart();

        // Save changes and continue to the last setup step.
        $this->_em->persist($station);
        $this->_em->flush();
    }
}