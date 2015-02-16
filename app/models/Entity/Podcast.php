<?php
namespace Entity;

use \Doctrine\Common\Collections\ArrayCollection;

/**
 * @Table(name="podcast")
 * @Entity
 */
class Podcast extends \DF\Doctrine\Entity
{
    use Traits\FileUploads;

    public function __construct()
    {
        $this->episodes = new ArrayCollection;
        $this->stations = new ArrayCollection;
    }

    /**
     * @Column(name="id", type="integer")
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /** @Column(name="name", type="string", length=150, nullable=true) */
    protected $name;

    /** @Column(name="country", type="string", length=50, nullable=true) */
    protected $country;

    public function getCountryName()
    {
        if ($this->country)
            return \PVL\Internationalization::getLanguageName($this->country);
        else
            return '';
    }

    /** @Column(name="description", type="text", nullable=true) */
    protected $description;

    /** @Column(name="image_url", type="string", length=100, nullable=true) */
    protected $image_url;

    public function setImageUrl($new_url)
    {
        $this->_processAndCropImage('image_url', $new_url, 150, 150);
    }

    public function getImageUrl()
    {
        return self::getArtistImage($this->image_url);
    }

    /** @Column(name="banner_url", type="string", length=100, nullable=true) */
    protected $banner_url;

    public function setBannerUrl($new_url)
    {
        $this->_processAndCropImage('banner_url', $new_url, 600, 300);
    }

    /** @Column(name="contact_email", type="string", length=255, nullable=true) */
    protected $contact_email;

    /** @Column(name="web_url", type="string", length=255, nullable=true) */
    protected $web_url;

    /** @Column(name="twitter_url", type="string", length=255, nullable=true) */
    protected $twitter_url;

    /** @Column(name="rss_url", type="string", length=255, nullable=true) */
    protected $rss_url;

    /** @Column(name="tumblr_url", type="string", length=255, nullable=true) */
    protected $tumblr_url;

    /** @Column(name="facebook_url", type="string", length=255, nullable=true) */
    protected $facebook_url;

    /** @Column(name="youtube_url", type="string", length=255, nullable=true) */
    protected $youtube_url;

    /** @Column(name="soundcloud_url", type="string", length=255, nullable=true) */
    protected $soundcloud_url;

    /** @Column(name="deviantart_url", type="string", length=255, nullable=true) */
    protected $deviantart_url;

    /** @Column(name="livestream_url", type="string", length=255, nullable=true) */
    protected $livestream_url;

    /** @Column(name="sync_timestamp", type="datetime", nullable=true) */
    protected $sync_timestamp;

    /** @Column(name="is_approved", type="boolean") */
    protected $is_approved;

    /**
     * @OnetoMany(targetEntity="PodcastEpisode", mappedBy="podcast")
     * @OrderBy({"timestamp" = "DESC"})
     */
    protected $episodes;

    /**
     * @ManyToMany(targetEntity="Station")
     * @JoinTable(name="podcast_on_station",
     *      joinColumns={@JoinColumn(name="podcast_id", referencedColumnName="id", onDelete="CASCADE")},
     *      inverseJoinColumns={@JoinColumn(name="station_id", referencedColumnName="id", onDelete="CASCADE")}
     * )
     */
    protected $stations;

    /**
     * Static Functions
     */

    public static function fetchLatest($num_to_fetch = 10)
    {
        $em = self::getEntityManager();

        $podcasts = \DF\Cache::get('homepage_podcasts');

        if (!$podcasts)
        {
            // Pull all recent episodes.
            $latest_podcast_episodes = $em->createQuery('SELECT pe FROM Entity\PodcastEpisode pe WHERE pe.timestamp > :threshold ORDER BY pe.timestamp DESC')
                ->setParameter('threshold', strtotime('-3 months'))
                ->getArrayResult();

            $eps = array();
            foreach($latest_podcast_episodes as $ep)
            {
                $pcid = $ep['podcast_id'];

                if (!isset($eps[$pcid]))
                    $eps[$pcid] = $ep;
            }

            // Bulk query for all podcasts related to recent episodes.
            $podcasts_raw = $em->createQuery('SELECT p, s FROM Entity\Podcast p LEFT JOIN p.stations s WHERE p.id IN (:podcasts) AND p.is_approved = 1')
                ->setParameter('podcasts', array_keys($eps))
                ->getArrayResult();

            foreach($podcasts_raw as $pc)
                $eps[$pc['id']]['podcast'] = $pc;

            // Reassign together into sensible array.
            $podcasts = array();
            foreach($eps as $ep)
            {
                $pc = $ep['podcast'];
                unset($ep['podcast']);
                $pc['episodes'] = array($ep);

                $podcasts[] = $pc;
            }

            array_slice($podcasts, 0, $num_to_fetch);

            \DF\Cache::save($podcasts, 'homepage_podcasts', array(), 300);
        }

        return $podcasts;
    }

    public static function fetchArray($cached = true)
    {
        $podcasts = \DF\Cache::get('podcasts');

        if (!$podcasts || !$cached)
        {
            $em = self::getEntityManager();
            $podcasts = $em->createQuery('SELECT p FROM '.__CLASS__.' p WHERE p.is_approved = 1 ORDER BY p.name ASC')
                ->getArrayResult();

            \DF\Cache::save($podcasts, 'podcasts', array(), 60);
        }

        return $podcasts;
    }

    public static function api($row_obj, $include_episodes = TRUE)
    {
        if ($row_obj instanceof self)
        {
            $row = $row_obj->toArray();

            $row['stations'] = array();
            if ($row_obj->stations)
            {
                foreach($row_obj->stations as $station)
                    $row['stations'][] = Station::api($station);
            }

            $row['episodes'] = array();
            if ($include_episodes && $row_obj->episodes)
            {
                foreach($row_obj->episodes as $episode)
                    $row['episodes'][] = PodcastEpisode::api($episode);
            }
        }
        else
        {
            $row = $row_obj;

            if (!isset($row['stations']))
                $row['stations'] = array();

            if (!isset($row['episodes']))
                $row['episodes'] = array();
        }

        $api_row = array(
            'id'        => (int)$row['id'],
            'name'      => $row['name'],
            'description' => $row['description'],
            'image_url' => \DF\Url::content(self::getArtistImage($row['image_url'])),
            'stations'  => $row['stations'],
        );

        if ($include_episodes)
            $api_row['episodes'] = $row['episodes'];

        $social_types = array_keys(self::getSocialTypes());
        foreach($social_types as $type_key)
            $api_row[$type_key] = $row[$type_key];

        return $api_row;
    }

    public static function getArtistImage($image_url)
    {
        if ($image_url)
        {
            $file_path = DF_UPLOAD_FOLDER.DIRECTORY_SEPARATOR.$image_url;
            if (file_exists($file_path))
                return $image_url;
        }

        return 'pvl_square.png';
    }

    public static function getSocialTypes()
    {
        return array(
            'web_url' => array(
                'name' => 'Web Site',
                'icon' => 'link',
            ),
            'contact_email' => array(
                'name' => 'E-mail Address',
                'icon' => 'email',
            ),
            'rss_url'   => array(
                'name' => 'RSS',
                'icon' => 'feed',
                'adapter' => 'Rss',
                'threshold' => '-6 months',
            ),
            'twitter_url'   => array(
                'name' => 'Twitter',
                'icon' => 'twitter',
                'adapter' => 'Twitter',
                'settings' => array(
                    'include_retweets'      => FALSE,
                    'always_featured'       => FALSE,
                    'use_retweet_count'     => FALSE,
                    'no_other_social_sites' => FALSE,
                    'max_featured_tweets'   => 3,
                ),
                'threshold' => '-1 week',
            ),
            'tumblr_url'    => array(
                'name' => 'Tumblr',
                'icon' => 'tumblr',
                'adapter' => 'Tumblr',
                'settings' => array(),
                'threshold' => '-1 week',
            ),
            'facebook_url'  => array(
                'name' => 'Facebook',
                'icon' => 'facebook',
                'adapter' => 'Facebook',
                'settings' => array(),
                'threshold' => '-1 week',
            ),
            'youtube_url'   => array(
                'name' => 'YouTube',
                'icon' => 'youtube',
                'adapter' => 'YouTube',
                'settings' => array(),
                'threshold' => '-6 months',
            ),
            'soundcloud_url' => array(
                'name' => 'SoundCloud',
                'icon' => 'soundcloud',
                'adapter' => 'SoundCloud',
                'settings' => array(),
                'threshold' => '-6 months',
            ),
            'deviantart_url' => array(
                'name' => 'DeviantArt',
                'icon' => 'deviantart',
                'adapter' => 'DeviantArt',
                'settings' => array(),
                'threshold' => '-6 months',
            ),
            'livestream_url' => array(
                'name' => 'LiveStream',
                'icon' => 'livestream',
                'adapter' => 'LiveStream',
                'settings' => array(),
                'threshold' => '-6 months',
            ),
        );
    }
}