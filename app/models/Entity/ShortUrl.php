<?php
namespace Entity;

use \Doctrine\Common\Collections\ArrayCollection;

/**
 * @Table(name="short_urls")
 * @Entity
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

    public function getUrl()
    {
        return self::getFullUrl($this->short_url);
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
        $station_short_names = Station::getShortNameLookup();

        if (isset($station_short_names[$origin]))
        {
            $station = $station_short_names[$origin];

            return \DF\Url::route(array(
                'module'    => 'default',
                'controller' => 'index',
                'action'    => 'index',
                'id'        => $station['id'],
                'autoplay'  => 'true',
            ));
        }

        // Check for a matching URL.
        $url = self::getRepository()->findOneBy(array('short_url' => $origin));

        if ($url instanceof self)
            return $url->long_url;

        // Default to the homepage.
        return \DF\Url::route();
    }

    public static function stationUrl(Station $station)
    {
        $short_name = $station->short_name;
        return self::getFullUrl($short_name);
    }

    public static function getFullUrl($short_url)
    {
        $short_url = ltrim($short_url, '/');
        return self::BASE_URL.'/'.$short_url;
    }
}