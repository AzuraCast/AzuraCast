<?php
namespace PVL;

use \Entity\Station;
use \Entity\Schedule;
use \Entity\Podcast;
use \Entity\PodcastEpisode;
use \Entity\Settings;
use \Entity\ShortUrl;

use \PVL\Service\PvlNode;

class NotificationManager
{
    public static function run($force = false)
    {
        $di = \Phalcon\Di::getDefault();

        \DF\Url::forceSchemePrefix(true);

        self::_runNetworkNews($di, $force);
        self::_runStationEvents($di, $force);
        self::_runPodcastEpisodes($di, $force);
    }

    /**
     * Run network news notifications.
     *
     * @param \Phalcon\DiInterface $di
     * @param bool $force
     */
    public static function _runNetworkNews(\Phalcon\DiInterface $di, $force = false)
    {
        $em = $di->get('em');

        $earliest_post = time() - (60 * 60 * 48);
        $latest_post = time();

        $news_items = $em->createQuery('SELECT nn
            FROM Entity\NetworkNews nn
            WHERE nn.source = :source
            AND nn.layout = :layout
            AND nn.sort_timestamp BETWEEN :start AND :end
            AND nn.is_notified = 0
            ORDER BY nn.sort_timestamp DESC')
            ->setParameter('source', 'tumblr')
            ->setParameter('layout', 'vertical')
            ->setParameter('start', $earliest_post)
            ->setParameter('end', $latest_post)
            ->setMaxResults(1)
            ->execute();

        if ($news_items) {
            $article = $news_items[0];

            $tweet_text = '#PVLive News: '.trim($article->title, ':').' - Read more:';
            $tweet_url = $article->web_url;
            $tweet_image = \PVL\Service\AmazonS3::path($article->image_url);

            self::notify($tweet_text, $tweet_url, $tweet_image, $force);

            $article->is_notified = true;
            $article->save();
        }
    }

    /**
     * Send notifications for new station events.
     *
     * @param \Phalcon\DiInterface $di
     * @param bool $force
     * @throws \DF\Exception
     */
    public static function _runStationEvents(\Phalcon\DiInterface $di, $force = false)
    {
        $notify_minutes = 15;

        $em = $di->get('em');

        $start_threshold = time();
        $end_threshold = time() + (60 * $notify_minutes);

        $schedule_items = $em->createQuery('SELECT s, st FROM Entity\Schedule s JOIN s.station st WHERE s.start_time >= :start AND s.start_time <= :end AND s.is_notified = 0')
            ->setParameter('start', $start_threshold)
            ->setParameter('end', $end_threshold)
            ->setMaxResults(1)
            ->execute();

        if ($schedule_items) {
            $schedule_item = $schedule_items[0];
            $station = $schedule_item->station;

            if ($station->twitter_url)
                $twitter_handle = '@' . array_pop(explode('/', $station->twitter_url));
            else
                $twitter_handle = $station->name;

            $tweet = 'Tune in to ' . $schedule_item->title . ' in ' . $notify_minutes . ' minutes on ' . $twitter_handle . '!';
            $tweet_url = $station->getShortUrl();

            PvlNode::push('schedule.event_upcoming', array(
                'event' => Schedule::api($schedule_item),
                'station' => Station::api($station),
            ));

            $image_url = NULL;
            if ($schedule_item->banner_url)
                $image_url = $schedule_item->banner_url;
            else if ($station->banner_url)
                $image_url = \PVL\Service\AmazonS3::path($station->banner_url);

            self::notify($tweet, $tweet_url, $image_url, $force);

            $schedule_item->is_notified = true;
            $schedule_item->save();
        }
    }

    /**
     * Send notifications for new podcast episodes.
     *
     * @param \Phalcon\DiInterface $di
     * @throws \DF\Exception
     */
    public static function _runPodcastEpisodes(\Phalcon\DiInterface $di, $force = false)
    {
        $em = $di->get('em');

        $start_threshold = time()-86400*7;
        $end_threshold = time();

        $podcast_episodes = $em->createQuery('SELECT pe, p
            FROM Entity\PodcastEpisode pe JOIN pe.podcast p
            WHERE pe.timestamp BETWEEN :start AND :end
            AND pe.is_notified = 0
            AND pe.is_active = 1
            AND p.is_approved = 1
            ORDER BY pe.timestamp DESC')
            ->setParameter('start', $start_threshold)
            ->setParameter('end', $end_threshold)
            ->setMaxResults(1)
            ->execute();

        if ($podcast_episodes)
        {
            $episode = $podcast_episodes[0];
            $podcast = $episode->podcast;

            $podcast_name = $podcast->name;
            if ($podcast->is_adult)
                $podcast_name = '[18+] '.$podcast_name;

            $title = \DF\Utilities::truncateText($episode->title, 110-strlen($podcast_name)-6);
            $tweet = $podcast_name.': "'.$title.'"';

            PvlNode::push('podcast.new_episode', array(
                'episode' => PodcastEpisode::api($episode),
                'podcast' => Podcast::api($podcast, false),
            ));

            $image_url = NULL;
            if ($podcast->banner_url)
                $image_url = \PVL\Service\AmazonS3::path($podcast->banner_url);

            // Special handling of podcast YT videos.
            if (stristr($podcast->web_url, 'youtube.com') !== false)
                $image_url = NULL;

            self::notify($tweet, $episode->getLocalUrl('twitter'), $image_url);

            // Set all episodes of the same podcast to be notified, to prevent spam.
            $em->createQuery('UPDATE Entity\PodcastEpisode pe SET pe.is_notified=1 WHERE pe.podcast_id = :podcast_id')
                ->setParameter('podcast_id', $podcast->id)
                ->execute();
        }
    }

    /**
     * Send an individual notification.
     *
     * @param string $message The main message body of the tweet.
     * @param null $url URL to reference as a link in the tweet.
     * @param null $image URL or filesystem path of an image to reference.
     * @param bool $force Trigger a notification even in development mode.
     * @return bool
     */
    public static function notify($message, $url = null, $image = null, $force = false)
    {
        static $twitter;

        \PVL\Debug::print_r(func_get_args());

        // Suppress notifications for non-production applications.
        if (DF_APPLICATION_ENV != "production" && !$force)
            return false;

        // Send through Notifico hook.
        $payload = $message.' - '.$url;
        \PVL\Service\Notifico::post($payload);

        // Send through Twitter.
        if (!$twitter)
        {
            $di = \Phalcon\Di::getDefault();
            $config = $di->get('config');

            $twitter_config = $config->apis->twitter->toArray();
            $twitter = new \tmhOAuth($twitter_config);
        }

        $message_length = 140;

        if ($url)
            $message_length -= 23;
        if ($image)
            $message_length -= 23;

        // Post t.co URLs directly instead of pulling down data.
        if (stristr($image, 't.co') !== FALSE)
        {
            $url .= ' '.$image;
            $image = NULL;
        }

        // Cut off the URL
        $tweet = \DF\Utilities::truncateText($message, $message_length);

        if ($url)
            $tweet .= ' '.$url;

        if ($image)
        {
            $image_data = base64_encode(file_get_contents($image));

            if (!empty($image_data))
            {
                $twitter->request('POST', 'https://upload.twitter.com/1.1/media/upload.json', array(
                    'media' => $image_data,
                ));

                \PVL\Debug::print_r($twitter->response['response']);
                $image_response = @json_decode($twitter->response['response'], true);

                if (isset($image_response['media_id_string']))
                {
                    $media_id = $image_response['media_id_string'];

                    $twitter->request('POST', 'https://api.twitter.com/1.1/statuses/update.json', array(
                        'status' => $tweet,
                        'media_ids' => array($media_id),
                    ));
                    \PVL\Debug::print_r($twitter->response['response']);
                }
                return true;
            }
        }

        $twitter->request('POST', 'https://api.twitter.com/1.1/statuses/update.json', array(
            'status' => $tweet,
        ));
        \PVL\Debug::print_r($twitter->response['response']);
        return true;
    }
}