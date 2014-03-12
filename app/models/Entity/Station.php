<?php
namespace Entity;

use \Doctrine\Common\Collections\ArrayCollection;

/**
 * @Table(name="station")
 * @Entity
 */
class Station extends \DF\Doctrine\Entity
{
    public function __construct()
    {
        $this->weight = 0;

        $this->is_active = 0;
        $this->is_special = 0;
        $this->requests_enabled = 0;
        $this->hide_if_inactive = 0;

        $this->history = new ArrayCollection;
        $this->managers = new ArrayCollection;
        $this->short_urls = new ArrayCollection;
        $this->media = new ArrayCollection;
    }

    /**
     * @Column(name="id", type="integer")
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /** @Column(name="is_active", type="boolean") */
    protected $is_active;

    /** @Column(name="is_special", type="boolean") */
    protected $is_special;

    /** @Column(name="name", type="string", length=100, nullable=true) */
    protected $name;

    public function getShortName()
    {
        return self::getStationShortName($this->name);
    }

    /** @Column(name="acronym", type="string", length=10, nullable=true) */
    protected $acronym;

    /** @Column(name="category", type="string", length=50, nullable=true) */
    protected $category;

    public function getCategoryIcon()
    {
        $categories = self::getCategories();
        return $categories[$this->category]['icon'];
    }
    public function getCategoryName()
    {
        $categories = self::getCategories();
        return $categories[$this->category]['name'];
    }

    /** @Column(name="type", type="string", length=50, nullable=true) */
    protected $type;

    /** @Column(name="weight", type="smallint") */
    protected $weight;

    /** @Column(name="genre", type="string", length=50, nullable=true) */
    protected $genre;

    /** @Column(name="description", type="text", nullable=true) */
    protected $description;

    /** @Column(name="owner", type="string", length=100, nullable=true) */
    protected $owner;

    /** @Column(name="image_url", type="string", length=100, nullable=true) */
    protected $image_url;

    public function setImageUrl($new_url)
    {
        if ($new_url)
        {
            if ($this->image_url && $this->image_url != $new_url)
                @unlink(DF_UPLOAD_FOLDER.DIRECTORY_SEPARATOR.$this->image_url);

            $this->image_url = $new_url;
        }
    }

    /** @Column(name="web_url", type="string", length=100, nullable=true) */
    protected $web_url;

    /** @Column(name="nowplaying_url", type="string", length=100, nullable=true) */
    protected $nowplaying_url;

    /** @Column(name="stream_url", type="string", length=150, nullable=true) */
    protected $stream_url;

    /** @Column(name="stream_alternate", type="text", nullable=true) */
    protected $stream_alternate;

    /** @Column(name="irc", type="string", length=25, nullable=true) */
    protected $irc;

    /** @Column(name="twitter_url", type="string", length=100, nullable=true) */
    protected $twitter_url;

    public function setTwitterUrl($url)
    {
        if (substr($url, 0, 4) == "http" || empty($url))
        {   
            $this->twitter_url = $url;
        }
        else
        {
            $url = 'http://www.twitter.com/'.str_replace('@', '', $url);
            $this->twitter_url = $url;
        }
    }

    /** @Column(name="gcal_url", type="string", length=150, nullable=true) */
    protected $gcal_url;

    /** @Column(name="nowplaying_artist", type="string", length=100, nullable=true) */
    protected $nowplaying_artist;

    /** @Column(name="nowplaying_title", type="string", length=100, nullable=true) */
    protected $nowplaying_title;

    /** @Column(name="nowplaying_text", type="string", length=150, nullable=true) */
    protected $nowplaying_text;

    /** @Column(name="nowplaying_image", type="string", length=255, nullable=true) */
    protected $nowplaying_image;

    /** @Column(name="nowplaying_listeners", type="smallint", nullable=true) */
    protected $nowplaying_listeners;

    /** @Column(name="nowplaying_data", type="json", nullable=true) */
    protected $nowplaying_data;

    /** @Column(name="hide_if_inactive", type="boolean") */
    protected $hide_if_inactive;


    /** @Column(name="requests_enabled", type="boolean") */
    protected $requests_enabled;

    /** @Column(name="requests_ccast_username", type="string", length=50, nullable=true) */
    protected $requests_ccast_username;

    /** @Column(name="requests_external_url", type="string", length=255, nullable=true) */
    protected $requests_external_url;


    /** @Column(name="admin_notes", type="text", nullable=true) */
    protected $admin_notes;

    /** @Column(name="admin_monitor_station", type="boolean", nullable=true) */
    protected $admin_monitor_station;

    /** @Column(name="station_notes", type="text", nullable=true) */
    protected $station_notes;


    /** @Column(name="intake_votes", type="json", nullable=true) */
    protected $intake_votes;

    /** @Column(name="deleted_at", type="datetime", nullable=true) */
    protected $deleted_at;

    /**
     * @OneToMany(targetEntity="SongHistory", mappedBy="station")
     * @OrderBy({"timestamp" = "DESC"})
     */
    protected $history;

    /**
     * @OneToMany(targetEntity="StationManager", mappedBy="station")
     */
    protected $managers;

    /**
     * @OneToMany(targetEntity="ShortUrl", mappedBy="station")
     */
    protected $short_urls;

    /**
     * @OneToMany(targetEntity="StationMedia", mappedBy="station")
     */
    protected $media;

    public function getShortUrl()
    {
        return ShortUrl::stationUrl($this);
    }

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
            $history = $sh['song'];
            $history['timestamp'] = $sh['timestamp'];
            
            $return[] = $history;
        }

        return $return;
    }

    public function canManage(User $user = null)
    {
        if ($user === null)
            $user = \DF\Auth::getLoggedInUser();

        $acl = \Zend_Registry::get('acl');

        if ($acl->userAllowed('manage stations', $user))
            return true;

        if ($this->managers)
        {
            foreach($this->managers as $manager)
            {
                if (strtolower($manager->email) == strtolower($user->email))
                    return true;
            }
        }

        return false;
    }

    /**
     * Static Functions
     */

    public static function getStationShortName($name)
    {
        return strtolower(preg_replace("/[^A-Za-z0-9_]/", '', str_replace(' ', '_', $name)));
    }

    public static function fetchAll()
    {
        $em = self::getEntityManager();
        return $em->createQuery('SELECT s FROM '.__CLASS__.' s WHERE s.is_active=1 ORDER BY s.category ASC, s.weight ASC')->execute();
    }
    
    public static function fetchArray($cached = true)
    {
        $stations = \DF\Cache::get('stations');

        if (!$stations || !$cached)
        {
            $em = self::getEntityManager();
            $stations = $em->createQuery('SELECT s FROM '.__CLASS__.' s WHERE s.is_active=1 ORDER BY s.category ASC, s.weight ASC')->getArrayResult();

            foreach($stations as &$station)
            {
                $station['short_name'] = self::getStationShortName($station['name']);
            }

            \DF\Cache::save($stations, 'stations', array(), 60);
        }

        return $stations;
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

    public static function getCategories()
    {
        return array(
            'event'     => array(
                'name' => 'Special Event Coverage',
                'icon' => 'icon-calendar',
                'stations' => array(),
            ),
            'audio'     => array(
                'name' => 'Radio Stations',
                'icon' => 'icon-music',
                'stations' => array(),
            ),
            'video'     => array(
                'name' => 'Video Streams',
                'icon' => 'icon-facetime-video',
                'stations' => array(),
            ),
        );
    }

    public static function getStationsInCategories()
    {
        $stations = \Entity\Station::fetchArray();

        $categories = self::getCategories();
        foreach($stations as $station)
            $categories[$station['category']]['stations'][] = $station;

        $special_event = Settings::getSetting('special_event', 0);
        if (!$special_event)
            unset($categories['event']);

        return $categories;
    }

    public static function canSeeStationCenter(User $user = null)
    {
        if ($user === null)
        {
            if (\DF\Auth::isLoggedIn())
                $user = \DF\Auth::getLoggedInUser();
            else
                return false;
        }

        $acl = \Zend_Registry::get('acl');
        if ($acl->userAllowed('manage stations', $user))
            return true;

        $em = self::getEntityManager();

        $manager_positions = StationManager::getRepository()->findBy(array('email' => strtolower($user->email)));
        return (count($manager_positions) > 0);
    }
}