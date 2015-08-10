<?php
namespace Entity;

use Doctrine\Common\Collections\ArrayCollection;
use PVL\Debug;

/**
 * @Table(name="podcast_sources")
 * @Entity
 */
class PodcastSource extends \DF\Doctrine\Entity
{
    public function __construct()
    {
        $this->is_active = true;

        $this->episodes = new ArrayCollection;
    }

    /**
     * @Column(name="id", type="integer")
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /** @Column(name="type", type="string", length=150, nullable=true) */
    protected $type;

    /** @Column(name="url", type="string", length=255, nullable=true) */
    protected $url;

    /** @Column(name="is_active", type="boolean") */
    protected $is_active;

    /**
     * @ManyToOne(targetEntity="Podcast", inversedBy="sources")
     * @JoinColumns({
     *   @JoinColumn(name="podcast_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    protected $podcast;

    /**
     * @OnetoMany(targetEntity="PodcastEpisode", mappedBy="source")
     */
    protected $episodes;

    /**
     * Process a podcast source and return remote data from it.
     *
     * @return array|bool
     */
    public function process()
    {
        Debug::log('Processing source: '.$this->type);

        $source_info = self::getSourceInfo($this->type);

        if (!isset($source_info['adapter']))
        {
            Debug::log('No suitable adapter found!');
            return FALSE;
        }

        $source_settings = (isset($source_info['settings'])) ? $source_info['settings'] : array();

        // Look for new news items.
        $class_name = '\\PVL\\NewsAdapter\\'.$source_info['adapter'];
        $news_items = $class_name::fetch($this->url, $source_settings);

        if (empty($news_items))
        {
            Debug::log('No news items found! Adapter: '.$class_name);
            return FALSE;
        }

        $new_episodes = array();
        foreach((array)$news_items as $item)
        {
            $guid = $item['guid'];
            $new_episodes[$guid] = array(
                'guid'      => $guid,
                'source_id' => $this->id,
                'timestamp' => $item['timestamp'],
                'title'     => $item['title'],
                'body'      => self::cleanUpText($item['body']),
                'summary'   => self::getSummary($item['body']),
                'web_url'   => $item['web_url'],
            );
        }

        Debug::print_r($new_episodes);

        return $new_episodes;
    }

    /**
     * Get processing information for the source specified (or all sources if null).
     *
     * @param null $type
     * @return array
     */
    public static function getSourceInfo($type = null)
    {
        $sources = array(
            'rss_url'   => array(
                'name'      => 'RSS Feed',
                'adapter'   => 'Rss',
                'threshold' => '-6 months',
            ),
            'twitter_url'   => array(
                'name'      => 'Twitter URL',
                'adapter'   => 'Twitter',
                'threshold' => '-1 week',
                'settings' => array(
                    'include_retweets'      => FALSE,
                    'always_featured'       => FALSE,
                    'use_retweet_count'     => FALSE,
                    'no_other_social_sites' => FALSE,
                    'max_featured_tweets'   => 3,
                ),
            ),
            'tumblr_url'    => array(
                'name'      => 'Tumblr URL',
                'adapter'   => 'Tumblr',
                'threshold' => '-1 week',
            ),
            'facebook_url'  => array(
                'name'      => 'Facebook Page URL',
                'adapter'   => 'Facebook',
                'threshold' => '-1 week',
            ),
            'youtube_url'   => array(
                'name'      => 'YouTube Account or Playlist URL',
                'adapter'   => 'YouTube',
                'threshold' => '-6 months',
            ),
            'soundcloud_url' => array(
                'name'      => 'SoundCloud URL',
                'adapter'   => 'SoundCloud',
                'threshold' => '-6 months',
            ),
            'deviantart_url' => array(
                'name'      => 'DeviantArt Account URL',
                'adapter'   => 'DeviantArt',
                'threshold' => '-6 months',
            ),
            'livestream_url' => array(
                'name'      => 'LiveStream URL',
                'adapter'   => 'LiveStream',
                'threshold' => '-6 months',
            ),
        );

        if ($type !== null)
            return $sources[$type];
        else
            return $sources;
    }

    /**
     * Generate dropdown select box options for sources.
     *
     * @return array
     */
    public static function getSourceSelect()
    {
        return \DF\Utilities::ipull(self::getSourceInfo(), 'name');
    }

    /**
     * Clean up text supplied in news adapters.
     *
     * @param $text
     * @return string
     */
    public static function cleanUpText($text)
    {
        $text = strip_tags($text);
        return trim($text);
    }

    /**
     * Generate a truncated summary of the podcast description.
     *
     * @param $text
     * @return mixed|string
     */
    public static function getSummary($text)
    {
        $text = self::cleanUpText($text);

        // Strip all but the first line.
        $text = strtok($text, "\n");

        // Remove URLs.
        $text = preg_replace('@(https?://([-\w\.]+[-\w])+(:\d+)?(/([\w/_\.#-]*(\?\S+)?[^\.\s])?).*$)@ui', ' ', $text);

        // Truncate to 300 characters.
        $text = \DF\Utilities::truncateText($text, 300);

        return $text;
    }
}