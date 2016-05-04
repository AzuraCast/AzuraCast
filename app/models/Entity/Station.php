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
    use Traits\FileUploads;

    public function __construct()
    {
        $this->history = new ArrayCollection;
        $this->managers = new ArrayCollection;

        $this->media = new ArrayCollection;
        $this->playlists = new ArrayCollection;
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

    /** @Column(name="name", type="string", length=100, nullable=true) */
    protected $name;

    public function getShortName()
    {
        return self::getStationShortName($this->name);
    }

    /** @Column(name="description", type="text", nullable=true) */
    protected $description;

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

    /** @Column(name="nowplaying_data", type="json", nullable=true) */
    protected $nowplaying_data;

    /** @Column(name="requests_enabled", type="boolean") */
    protected $requests_enabled;

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
     * @OneToMany(targetEntity="StationPlaylist", mappedBy="station")
     */
    protected $playlists;

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
            $user = \App\Auth::getLoggedInUser();

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
        $stations = \App\Cache::get('stations');

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

            \App\Cache::save($stations, 'stations', array(), 60);
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

        /*
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
        */

        // $api['player_url'] = ShortUrl::stationUrl($api['shortcode']);

        return $api;
    }
}