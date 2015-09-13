<?php
namespace PVL;

use DF\Image;

use Entity\NetworkNews;
use Entity\Podcast;
use Entity\Schedule;
use Entity\Station;
use Entity\Convention;

class NewsManager
{
    const DESCRIPTION_LENGTH = 300;

    public static function run()
    {
        $di = \Phalcon\Di::getDefault();
        $em = $di->get('em');

        // Assemble news items from other sources.
        $news_items_raw = array(
            self::_runTumblrNews($di),
            self::_runConventionPromotions($di),
            self::_runPodcastEpisodes($di),
            self::_runScheduleItems($di),
        );

        $news_items = array();
        foreach($news_items_raw as $item_group)
            $news_items = array_merge($news_items, (array)$item_group);

        // Replace/insert into database.
        $news_stats = array(
            'inserted' => 0,
            'updated' => 0,
            'deleted' => 0,
        );

        if (!empty($news_items))
        {
            $old_news_raw = NetworkNews::fetchAll();
            $old_news = array();

            foreach($old_news_raw as $old_row)
                $old_news[$old_row->id] = $old_row;

            // Update or insert items.
            foreach($news_items as $item)
            {
                if (isset($old_news[$item['id']])) {
                    $news_stats['updated']++;
                    $record = $old_news[$item['id']];
                } else {
                    $news_stats['inserted']++;
                    $record = new NetworkNews;
                }

                $record->fromArray($item);
                $em->persist($record);

                unset($old_news[$item['id']]);
            }

            // Delete unreferenced items.
            foreach($old_news as $item_id => $item_to_remove)
            {
                $news_stats['deleted']++;
                $em->remove($item_to_remove);
            }

            $em->flush();

            // Flush cache of homepage news.
            \DF\Cache::remove('homepage_featured_news');
        }

        \PVL\Debug::print_r($news_stats);
    }

    public static function _runTumblrNews(\Phalcon\DiInterface $di)
    {
        $news_items = array();
        $config = $di->get('config');

        // Pull featured images.
        $timestamp_threshold = strtotime('-6 weeks');

        $api_params = array(
            'api_key' => $config->apis->tumblr->key,
            'limit' => 10,
        );
        $api_url = 'http://api.tumblr.com/v2/blog/news.ponyvillelive.com/posts/photo?' . http_build_query($api_params);

        $results_raw = @file_get_contents($api_url);
        if ($results_raw)
        {
            $results = json_decode($results_raw, true);
            $posts = $results['response']['posts'];

            foreach ((array)$posts as $post)
            {
                if ($post['timestamp'] < $timestamp_threshold)
                    continue;

                $image = null;
                $post_style = 'vertical';
                $image_is_valid = false;

                foreach ((array)$post['photos'] as $photo)
                {
                    $image = $photo['original_size'];

                    if ($image['width'] == 600 && $image['height'] == 300) // New vertical style.
                    {
                        $image_is_valid = true;
                        $post_style = 'vertical';
                        break;
                    } elseif ($image['width'] == 1150 && $image['height'] == 200) // Older horizontal style.
                    {
                        $image_is_valid = true;
                        $post_style = 'horizontal';
                        break;
                    }
                }

                if (!$image_is_valid)
                    continue;

                // Copy the image to the local static directory (for SSL and other caching support).
                $image_url = $image['url'];
                $image_url_basename = basename($image_url);

                $local_path_base = 'rotators/' . $image_url_basename;
                $local_url = $local_path_base;

                $local_path = DF_INCLUDE_TEMP . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $local_path_base);
                $s3_path = Service\AmazonS3::path($local_url);

                if (!file_exists($s3_path))
                {
                    @mkdir(dirname($local_path));
                    @copy($image_url, $local_path);

                    // Optimize image for fast display.
                    Image::resizeImage($local_path, $local_path, $image['width'], $image['height']);

                    Service\AmazonS3::upload($local_path, $local_path_base);
                }

                $tags = array_map('strtolower', (array)$post['tags']);
                if (in_array('archive', $tags))
                    continue;

                $description = strip_tags($post['caption']);
                if (strpos($description, ':') === FALSE)
                    break;

                list($title, $description) = explode(':', $description, 2);
                $description = Utilities::truncateText($description, self::DESCRIPTION_LENGTH);

                $news_items[] = array(
                    'id' => 'tumblr_' . $post['id'],
                    'title' => trim($title),
                    'source' => 'tumblr',
                    'body' => trim($description),
                    'image_url' => \PVL\Url::upload($local_url),
                    'web_url' => $post['post_url'],
                    'layout' => $post_style,
                    'tags' => (array)$post['tags'],
                    'sort_timestamp' => $post['timestamp'],
                    'display_timestamp' => $post['timestamp'],
                );
            }
        }

        return $news_items;
    }

    public static function _runConventionPromotions(\Phalcon\DiInterface $di)
    {
        $news_items = array();
        $em = $di->get('em');

        // Pull all recent or upcoming conventions.
        $conventions_raw = $em->createQuery('SELECT c, ca FROM Entity\Convention c LEFT JOIN c.archives ca
            WHERE (c.start_date BETWEEN :threshold_start AND :threshold_end)
            AND (c.image_url IS NOT NULL AND c.image_url != \'\')
            ORDER BY c.start_date DESC, ca.created_at DESC')
            ->setParameter('threshold_start', date('Y-m-d', strtotime('-2 months')))
            ->setParameter('threshold_end', date('Y-m-d', strtotime('+1 year')))
            ->getArrayResult();

        foreach((array)$conventions_raw as $convention)
        {
            if (empty($convention['web_url']))
                continue;

            $create_post = false;
            $start_date = $convention['start_date']->getTimestamp();
            $end_date = $convention['end_date']->getTimestamp();

            $post_item = array(
                'id'        => 'convention_'.$convention['id'],
                'title'     => $convention['name'],
                'source'    => 'convention',
                'body'      => '',
                'image_url' => \PVL\Url::upload($convention['image_url']),
                'web_url'   => $convention['web_url'],
                'layout'    => 'vertical',
                'tags'      => array($convention['name'], 'Conventions'),
                'sort_timestamp' => $start_date,
                'display_timestamp' => $start_date,
            );

            if ($start_date > time())
            {
                // Pre-convention: Check for discount code promotion.
                if (!empty($convention['discount_code']) && $start_date >= time()+86400*14)
                {
                    $create_post = true;
                    $convention_details = array(
                        ':convention'   => $convention['name'],
                        ':discount'     => $convention['discount_code'],
                    );

                    $post_title_raw = 'Register for :convention with Discount Code ":discount"';
                    $post_item['title'] = strtr($post_title_raw, $convention_details);

                    $post_body_raw = 'Ponyville Live! is partnering with :convention to offer a special discount to our visitors. Visit the convention\'s registration page and enter discount code ":discount" to save on your registration!';
                    $post_item['body'] = strtr($post_body_raw, $convention_details);

                    // More distant conventions sort lower on the list than closer ones.
                    $time_diff = $start_date - time();
                    $post_item['sort_timestamp'] = time() - round($time_diff / 60);
                }
            }
            elseif ($start_date <= time()+86400*7 && $end_date >= time())
            {
                // Mid-convention: Check for live coverage.
                $coverage_types = array(
                    Convention::COVERAGE_STREAMING  => 'Ponyville Live! is streaming :convention live on the web! Check our web site for stream details, and check the convention\'s web site for official schedule updates and more information on the convention.',
                    Convention::COVERAGE_FULL       => 'Ponyville Live! will be providing full recording coverage of :convention. Check out the convention\'s web site for more information on the convention, and come back to our homepage for footage updates after the convention.',
                    Convention::COVERAGE_PARTIAL    => 'Ponyville Live! will be providing limited coverage of :convention. Check out the convention\'s web site for more information on the convention, and come back to our homepage for footage updates after the convention.',
                );

                if (isset($coverage_types[$convention['coverage_level']]))
                {
                    $create_post = true;
                    $convention_details = array(
                        ':convention'   => $convention['name'],
                        ':range'        => Convention::getDateRange($convention['start_date'], $convention['end_date']),
                    );

                    $post_title_raw = ":convention\n:range";
                    $post_item['title'] = strtr($post_title_raw, $convention_details);

                    $post_item['body'] = strtr($coverage_types[$convention['coverage_level']], $convention_details);
                    $post_item['sort_timestamp'] = $end_date+86400;
                }
            }

            if ($create_post)
                $news_items[] = $post_item;
        }

        return $news_items;
    }

    public static function _runPodcastEpisodes(\Phalcon\DiInterface $di)
    {
        $news_items = array();
        $em = $di->get('em');

        // Pull podcast episodes.
        $podcasts_raw = $em->createQuery('SELECT p, pe, ps FROM Entity\Podcast p LEFT JOIN p.episodes pe JOIN pe.source ps
            WHERE (p.banner_url IS NOT NULL AND p.banner_url != \'\')
            AND p.is_approved = 1
            AND pe.timestamp >= :threshold
            AND pe.is_active = 1
            ORDER BY p.id ASC, pe.timestamp DESC')
            ->setParameter('threshold', strtotime('-1 month'))
            ->getArrayResult();

        foreach ((array)$podcasts_raw as $podcast)
        {
            foreach ($podcast['episodes'] as $ep)
            {
                if (empty($ep['body']))
                    continue;

                $title = trim($ep['title']);

                if ($podcast['is_adult'])
                    $title = '[18+] '.$title;

                $news_items[] = array(
                    'id' => 'podcast_' . $ep['guid'],
                    'title' => $title,
                    'source' => 'podcast',
                    'body' => $ep['summary'],
                    'image_url' => \Entity\PodcastEpisode::getEpisodeRotatorUrl($ep, $podcast, $ep['source']),
                    'web_url' => \Entity\PodcastEpisode::getEpisodeLocalUrl($ep, 'pvlnews'),
                    'layout' => 'vertical',
                    'tags' => array($podcast['name'], 'Podcast Episodes'),
                    'sort_timestamp' => $ep['timestamp'],
                    'display_timestamp' => $ep['timestamp'],
                );
                break;
            }
        }

        return $news_items;
    }

    public static function _runScheduleItems(\Phalcon\DiInterface $di)
    {
        $news_items = array();
        $em = $di->get('em');

        // Pull promoted schedule items.
        $events_raw = $em->createQuery('SELECT st, s FROM \Entity\Schedule s
            JOIN s.station st
            WHERE (s.end_time >= :current AND s.start_time <= :future)
            AND (st.banner_url != \'\' AND st.banner_url IS NOT NULL)
            AND s.is_promoted = 1
            ORDER BY s.start_time ASC')
            ->setParameter('current', time())
            ->setParameter('future', strtotime('+1 week'))
            ->getArrayResult();

        $promoted_stations = array();

        foreach ($events_raw as $event)
        {
            $station_id = $event['station_id'];
            if (isset($promoted_stations[$station_id]))
                continue;
            else
                $promoted_stations[$station_id] = true;

            $range = Schedule::getRangeText($event['start_time'], $event['end_time'], $event['is_all_day']);

            $description = array();
            $description[] = 'Coming up on ' . $event['station']['name'];
            $description[] = $range;

            if (!empty($event['body']))
                $description[] = $event['body'];

            // Manually adjust the sorting timestamp for the event if it is in the future.
            $sort_timestamp = $event['start_time'];
            if ($sort_timestamp >= time())
                $sort_timestamp = time() - ($sort_timestamp - time());

            $news_items[] = array(
                'id' => 'schedule_' . $event['guid'],
                'title' => trim($event['title']),
                'source' => 'station',
                'body' => implode('<br>', $description),
                'image_url' => \PVL\Url::upload($event['station']['banner_url']),
                'web_url' => $event['station']['web_url'],
                'layout' => 'vertical',
                'tags' => array($event['station']['name'], 'Events'),
                'sort_timestamp' => $sort_timestamp,
                'display_timestamp' => $event['start_time'],
            );
            break;
        }

        return $news_items;
    }


}