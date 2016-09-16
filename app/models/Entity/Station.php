<?php
namespace Entity;

use \Doctrine\Common\Collections\ArrayCollection;

/**
 * @Table(name="station")
 * @Entity
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
     * @PreDelete
     */
    public function deleting()
    {
        $ba = $this->getBackendAdapter();
        $fa = $this->getFrontendAdapter();

        $ba->stop();
        $fa->stop();
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
    public function getFrontendAdapter()
    {
        $adapters = self::getFrontendAdapters();

        if (!isset($adapters['adapters'][$this->frontend_type]))
            throw new \Exception('Adapter not found: '.$this->frontend_type);

        $class_name = $adapters['adapters'][$this->frontend_type]['class'];
        return new $class_name($this);
    }

    /** @Column(name="backend_type", type="string", length=100, nullable=true) */
    protected $backend_type;

    /** @Column(name="backend_config", type="json", nullable=true) */
    protected $backend_config;

    /**
     * @return \App\Radio\Backend\AdapterAbstract
     * @throws \Exception
     */
    public function getBackendAdapter()
    {
        $adapters = self::getBackendAdapters();

        if (!isset($adapters['adapters'][$this->backend_type]))
            throw new \Exception('Adapter not found: '.$this->backend_type);

        $class_name = $adapters['adapters'][$this->backend_type]['class'];
        return new $class_name($this);
    }

    /** @Column(name="description", type="text", nullable=true) */
    protected $description;

    public function getRadioStreamUrl()
    {
        $frontend_adapter = $this->getFrontendAdapter();
        return $frontend_adapter->getStreamUrl();
    }

    /** @Column(name="radio_base_dir", type="string", length=255, nullable=true) */
    protected $radio_base_dir;

    public function setRadioBaseDir($new_dir)
    {
        if ($new_dir != $this->radio_base_dir)
        {
            $this->radio_base_dir = $new_dir;

            @mkdir($this->getRadioMediaDir(), 0777, TRUE);
            @mkdir($this->getRadioPlaylistsDir(), 0777, TRUE);
            @mkdir($this->getRadioConfigDir(), 0777, TRUE);
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
     * @OrderBy({"weight" = "DESC"})
     */
    protected $playlists;

    public function getRecentHistory($num_entries = 5)
    {
        $em = self::getEntityManager();
        $history = $em->createQuery('SELECT sh, s FROM Entity\SongHistory sh JOIN sh.song s WHERE sh.station_id = :station_id ORDER BY sh.id DESC')
            ->setParameter('station_id', $this->id)
            ->setMaxResults($num_entries)
            ->getArrayResult();

        $return = array();
        foreach($history as $sh)
        {
            $history = array(
                'played_at'     => $sh['timestamp_start'],
                'song'          => Song::api($sh['song']),
            );
            $return[] = $history;
        }

        return $return;
    }

    public function canManage(User $user = null)
    {
        $di = \Phalcon\Di::getDefault();

        if ($user === null)
        {
            $auth = $di->get('auth');
            $user = $auth->getLoggedInUser();
        }

        $acl = $di->get('acl');
        if ($acl->userAllowed('manage stations', $user))
            return true;

        return ($this->managers->contains($user));
    }

    /**
     * Static Functions
     */

    public static function getStationShortName($name)
    {
        return strtolower(preg_replace("/[^A-Za-z0-9_]/", '', str_replace(' ', '_', trim($name))));
    }

    public static function getStationClassName($name)
    {
        $name = preg_replace("/[^A-Za-z0-9_ ]/", '', $name);
        $name = str_replace('_', ' ', $name);
        $name = str_replace(' ', '', $name);
        return $name;
    }

    public static function fetchAll()
    {
        $em = self::getEntityManager();
        return $em->createQuery('SELECT s FROM '.__CLASS__.' s ORDER BY s.name ASC')->execute();
    }

    public static function fetchArray($cached = true)
    {
        $em = self::getEntityManager();
        $stations = $em->createQuery('SELECT s FROM '.__CLASS__.' s ORDER BY s.name ASC')
            ->getArrayResult();

        foreach($stations as &$station)
        {
            $station['short_name'] = self::getStationShortName($station['name']);
        }

        return $stations;
    }

    public static function fetchSelect($add_blank = FALSE, \Closure $display = NULL, $pk = 'id', $order_by = 'name')
    {
        $select = array();

        // Specify custom text in the $add_blank parameter to override.
        if ($add_blank !== FALSE)
            $select[''] = ($add_blank === TRUE) ? 'Select...' : $add_blank;

        // Build query for records.
        $results = self::fetchArray();

        // Assemble select values and, if necessary, call $display callback.
        foreach((array)$results as $result)
        {
            $key = $result[$pk];
            $value = ($display === NULL) ? $result['name'] : $display($result);
            $select[$key] = $value;
        }

        return $select;
    }

    public static function getShortNameLookup($cached = true)
    {
        $stations = self::fetchArray($cached);

        $lookup = array();
        foreach ($stations as $station)
        {
            $lookup[$station['short_name']] = $station;
        }

        return $lookup;
    }

    public static function findByShortCode($short_code)
    {
        $short_names = self::getShortNameLookup();

        if (isset($short_names[$short_code]))
        {
            $id = $short_names[$short_code]['id'];
            return self::find($id);
        }

        return NULL;
    }

    // Retrieve the API version of the object/array.
    public static function api($row)
    {
        if ($row instanceof self)
            $row = $row->toArray();

        $api = array(
            'id'        => (int)$row['id'],
            'name'      => $row['name'],
            'shortcode' => self::getStationShortName($row['name']),
        );

        return $api;
    }

    public static function create($data)
    {
        $em = self::getEntityManager();

        $station = new self;
        $station->fromArray($data);

        // Create path for station.
        $station_base_dir = realpath(APP_INCLUDE_ROOT.'/..').'/stations';
        @mkdir($station_base_dir);

        $station_dir = $station_base_dir.'/'.$station->getShortName();
        $station->radio_base_dir = $station_dir;

        // Generate station ID.
        $station->save();

        // Scan directory for any existing files.
        set_time_limit(600);
        \App\Sync\Media::importMusic($station);
        $em->refresh($station);

        \App\Sync\Media::importPlaylists($station);
        $em->refresh($station);

        // Load configuration from adapter to pull source and admin PWs.
        $frontend_adapter = $station->getFrontendAdapter();
        $frontend_adapter->read();

        // Write initial XML file (if it doesn't exist).
        $frontend_adapter->write();
        $frontend_adapter->restart();

        // Write an empty placeholder configuration.
        $backend_adapter = $station->getBackendAdapter();
        $backend_adapter->write();
        $backend_adapter->restart();

        // Save changes and continue to the last setup step.
        $station->save();
    }

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
}