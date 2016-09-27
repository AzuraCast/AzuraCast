<?php
namespace Entity;

use \Doctrine\Common\Collections\ArrayCollection;
use Interop\Container\ContainerInterface;

/**
 * @Table(name="station")
 * @Entity(repositoryClass="Repository\StationRepository")
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

            mkdir($this->radio_base_dir, 0777);
            mkdir($this->getRadioMediaDir(), 0777);
            mkdir($this->getRadioPlaylistsDir(), 0777);
            mkdir($this->getRadioConfigDir(), 0777);
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