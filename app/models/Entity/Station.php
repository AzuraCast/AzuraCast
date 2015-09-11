<?php
namespace Entity;

use \Doctrine\Common\Collections\ArrayCollection;

/**
 * @Table(name="station")
 * @Entity
 * @HasLifecycleCallbacks
 */
class Station extends \DF\Doctrine\Entity
{
    use Traits\FileUploads;

    public function __construct()
    {
        $this->weight = 0;
        $this->affiliation = 'syndicated';

        $this->is_active = 0;
        $this->requests_enabled = 0;

        $this->streams = new ArrayCollection;
        $this->history = new ArrayCollection;
        $this->managers = new ArrayCollection;
        $this->short_urls = new ArrayCollection;
        $this->media = new ArrayCollection;
    }

    /**
     * @PreRemove
     */
    public function deleting()
    {
        $this->_deleteFile('image_url');
        $this->_deleteFile('banner_url');
    }

    /**
     * @Column(name="id", type="integer")
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /** @Column(name="is_active", type="boolean") */
    protected $is_active;

    /** @Column(name="name", type="string", length=100, nullable=true) */
    protected $name;

    public function getShortName()
    {
        return self::getStationShortName($this->name);
    }

    /** @Column(name="description", type="text", nullable=true) */
    protected $description;

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

    /** @Column(name="affiliation", type="string", length=50, nullable=true) */
    protected $affiliation;

    /** @Column(name="weight", type="smallint") */
    protected $weight;

    /** @Column(name="genre", type="string", length=50, nullable=true) */
    protected $genre;

    /** @Column(name="country", type="string", length=5, nullable=true) */
    protected $country;

    public function getCountryName()
    {
        if ($this->country)
            return \PVL\Internationalization::getCountryName($this->country);
        else
            return '';
    }

    /** @Column(name="image_url", type="string", length=100, nullable=true) */
    protected $image_url;

    public function setImageUrl($new_url)
    {
        $this->_processAndCropImage('image_url', $new_url, 150, 150);
    }

    /** @Column(name="banner_url", type="string", length=100, nullable=true) */
    protected $banner_url;

    public function setBannerUrl($new_url)
    {
        $this->_processAndCropImage('banner_url', $new_url, 600, 300);
    }

    /** @Column(name="contact_email", type="string", length=255, nullable=true) */
    protected $contact_email;

    /** @Column(name="web_url", type="string", length=100, nullable=true) */
    protected $web_url;

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

    /** @Column(name="facebook_url", type="string", length=150, nullable=true) */
    protected $facebook_url;

    /** @Column(name="tumblr_url", type="string", length=150, nullable=true) */
    protected $tumblr_url;

    /** @Column(name="nowplaying_data", type="json", nullable=true) */
    protected $nowplaying_data;

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
     * @OneToMany(targetEntity="StationStream", mappedBy="station")
     * @OrderBy({"name" = "ASC"})
     */
    protected $streams;

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

    public function getRecentHistory(StationStream $stream, $num_entries = 5)
    {
        $em = self::getEntityManager();
        $history = $em->createQuery('SELECT sh, s FROM Entity\SongHistory sh JOIN sh.song s WHERE sh.station_id = :station_id AND sh.stream_id = :stream_id ORDER BY sh.id DESC')
            ->setParameter('station_id', $this->id)
            ->setParameter('stream_id', $stream->id)
            ->setMaxResults($num_entries)
            ->getArrayResult();

        $return = array();
        foreach($history as $sh)
        {
            $history = array(
                'played_at'     => $sh['timestamp'],
                'song'          => Song::api($sh['song']),
            );
            $return[] = $history;
        }

        return $return;
    }

    public function canManage(User $user = null)
    {
        if ($user === null)
            $user = \DF\Auth::getLoggedInUser();

        $di = \Phalcon\Di::getDefault();
        $acl = $di->get('acl');

        if ($acl->userAllowed('manage stations', $user))
            return true;

        return ($this->managers->contains($user));
    }

    public function isPlaying()
    {
        $stream = $this->getDefaultStream();
        return $stream->isPlaying();
    }

    public function setDefaultStream($sid)
    {
        if ($sid instanceof StationStream)
            $sid = $sid->id;

        $em = self::getEntityManager();

        foreach($this->streams as $stream)
        {
            if ($stream->id == $sid)
                $stream->is_default = true;
            else
                $stream->is_default = false;

            $em->persist($stream);
        }

        $em->flush();

        // Ensure at least one stream is "default" for the station.
        $this->checkDefaultStream();
    }

    public function getDefaultStream()
    {
        foreach($this->streams as $stream)
        {
            if ($stream->is_default)
                return $stream;
        }

        return NULL;
    }

    public function checkDefaultStream()
    {
        if (count($this->streams) == 0)
            return false;

        $has_default = false;

        foreach($this->streams as $stream)
        {
            if ($stream->is_default)
                $has_default = true;
        }

        if (!$has_default)
        {
            $stream = $this->streams->first();
            $stream->is_default = true;
            $stream->save();
        }
    }

    /**
     * Static Functions
     */

    public static function getStationShortName($name)
    {
        return strtolower(preg_replace("/[^A-Za-z0-9_]/", '', str_replace(' ', '_', $name)));
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
        return $em->createQuery('SELECT s, ss FROM '.__CLASS__.' s LEFT JOIN s.streams ss WHERE s.is_active = 1 ORDER BY s.category ASC, s.weight ASC')->execute();
    }

    public static function fetchArray($cached = true)
    {
        $stations = \DF\Cache::get('stations');

        if (!$stations || !$cached)
        {
            $em = self::getEntityManager();
            $stations = $em->createQuery('SELECT s, ss FROM '.__CLASS__.' s
                LEFT JOIN s.streams ss
                WHERE s.is_active = 1 AND s.category IN (:types)
                ORDER BY s.category ASC, s.weight ASC')
                ->setParameter('types', array('audio', 'video'))
                ->getArrayResult();

            foreach($stations as &$station)
            {
                $station['short_name'] = self::getStationShortName($station['name']);

                foreach((array)$station['streams'] as $stream)
                {
                    if ($stream['is_default'])
                    {
                        $station['default_stream_id'] = $stream['id'];
                        $station['stream_url'] = $stream['stream_url'];
                    }
                }
            }

            \DF\Cache::save($stations, 'stations', array(), 60);
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

    public static function getCategories()
    {
        return array(
            'audio'    => array(
                'name' => 'Radio Stations',
                'icon' => 'icon-music',
                'stations' => array(),
            ),
            'video'    => array(
                'name' => 'Video Streams',
                'icon' => 'icon-facetime-video',
                'stations' => array(),
            ),
            'internal' => array(
                'name' => 'Internal Tracking Station',
                'icon' => 'icon-cog',
                'stations' => array(),
            ),
        );
    }

    public static function getStationsInCategories()
    {
        $stations = self::fetchArray();

        $categories = self::getCategories();
        foreach($stations as $station)
            $categories[$station['category']]['stations'][] = $station;

        return $categories;
    }

    public static function getCategorySelect()
    {
        $cats_raw = self::getCategories();
        $cats = array();

        foreach($cats_raw as $cat_key => $cat_info)
            $cats[$cat_key] = $cat_info['name'];

        return $cats;
    }

    public static function getAffiliationSelect()
    {
        return array(
            'partner'       => 'PVL Partner Station',
            'syndicated'    => 'Syndicated Station',
        );
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
            'genre'     => $row['genre'],
            'category'  => $row['category'],
            'affiliation' => $row['affiliation'],
            'image_url' => \PVL\Url::upload($row['image_url']),
            'web_url'   => $row['web_url'],
            'twitter_url' => $row['twitter_url'],
            'irc'       => $row['irc'],
            'sort_order' => (int)$row['weight'],
        );

        if (isset($row['streams']))
        {
            $api['streams'] = array();

            foreach ((array)$row['streams'] as $stream)
            {
                $api['streams'][] = StationStream::api($stream);

                // Set first stream as default, override if a later stream is explicitly default.
                if ($stream['is_default'] || !isset($api['default_stream_id']))
                {
                    $api['default_stream_id'] = (int)$stream['id'];
                    $api['stream_url'] = $stream['stream_url'];
                }
            }
        }

        $api['player_url'] = ShortUrl::stationUrl($api['shortcode']);

        if ($row['requests_enabled'])
            $api['request_url'] = \DF\Url::route(array('module' => 'default', 'controller' => 'station', 'action' => 'request', 'id' => $row['id']));
        else
            $api['request_url'] = '';

        return $api;
    }
}