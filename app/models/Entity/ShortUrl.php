<?php
namespace Entity;

use \Doctrine\Common\Collections\ArrayCollection;

/**
 * @Table(name="short_urls")
 * @Entity
 * @HasLifecycleCallbacks
 */
class ShortUrl extends \DF\Doctrine\Entity
{
    const BASE_URL = 'http://pvlive.me';

    public function __construct()
    {
        $this->timestamp = time();
    }

    /**
     * @Column(name="id", type="integer")
     * @Id
     * @GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /** @Column(name="station_id", type="integer", nullable=true) */
    protected $station_id;

    /** @Column(name="short_url", type="string", length=100) */
    protected $short_url;

    public function setShortUrl($new_short_url)
    {
        $this->short_url = preg_replace("/[^A-Za-z0-9_\-]/", '', $new_short_url);
    }

    public function getUrl()
    {
        return self::getFullUrl($this->short_url);
    }

    public function checkUrl()
    {
        if (empty($this->short_url))
            $this->short_url = substr(md5($this->long_url), 0, 10);

        // Check for a station URL.
        $station_urls = self::stationUrls();
        if (isset($station_urls[$this->short_url]))
            return false;

        $records = self::getRepository()->findBy(array('short_url' => $this->short_url));

        // Updating an existing record, exclude existing item.
        if ($this->id)
            return (count($records) <= 1);
        else
            return (count($records) == 0);
    }

    /** @Column(name="long_url", type="string", length=300) */
    protected $long_url;

    /** @Column(name="timestamp", type="integer") */
    protected $timestamp;

    /**
     * @ManyToOne(targetEntity="Station", inversedBy="short_urls")
     * @JoinColumns({
     *   @JoinColumn(name="station_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    protected $station;

    /**
     * Static Functions
     */

    public static function parse($origin)
    {
        $origin = ltrim($origin, '/');

        // Check for empty URL first.
        if (empty($origin))
            return \DF\Url::route();

        // Check for a station's short code (overrides manual input URLs).
        $station_urls = self::stationUrls();

        if (isset($station_urls[$origin]))
            return $station_urls[$origin];

        // Check for a convention archive page (overrides manual input URLs).
        $convention_urls = self::conventionUrls();

        if (isset($convention_urls[$origin]))
            return $convention_urls[$origin];

        // Check for a matching URL.
        $url = self::getRepository()->findOneBy(array('short_url' => $origin));

        if ($url instanceof self)
            return $url->long_url;

        // Default to the homepage.
        return \DF\Url::route();
    }

    public static function stationUrls()
    {
        $urls = array();
        $short_names = Station::getShortNameLookup();

        foreach($short_names as $short_name => $record)
        {
            $urls[$short_name] = \DF\Url::route(array(
                'module'    => 'default',
                'controller' => 'index',
                'action'    => 'index',
                'id'        => $record['id'],
                'autoplay'  => 'true',
            ));
        }

        return $urls;
    }

    public static function conventionUrls()
    {
        $urls = array();
        $short_names = Convention::getShortNameLookup();

        foreach($short_names as $short_name => $record)
        {
            $urls[$short_name] = \DF\Url::route(array(
                'module'    => 'default',
                'controller' => 'convention',
                'action'    => 'archive',
                'id'        => $record['id'],
            ));
        }

        return $urls;
    }

    public static function stationUrl($station)
    {
        if ($station instanceof Station)
            $short_name = $station->short_name;
        else
            $short_name = $station;

        return self::getFullUrl($short_name);
    }

    public static function getFullUrl($short_url)
    {
        $short_url = ltrim($short_url, '/');
        return self::BASE_URL.'/'.$short_url;
    }
}