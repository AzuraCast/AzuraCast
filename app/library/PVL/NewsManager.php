<?php
namespace PVL;

use Entity\NetworkNews;
use Entity\Podcast;
use Entity\Schedule;
use Entity\Station;

class NewsManager
{
    const DESCRIPTION_LENGTH = 300;

    public static function syncNetwork()
    {
        $di = \Phalcon\Di::getDefault();
        $em = $di->get('em');
        $config = $di->get('config');

        $news_items = array();

        // Pull featured images.
        $timestamp_threshold = strtotime('-6 weeks');

        $api_params = array(
            'api_key'       => $config->apis->tumblr->key,
            'limit'         => 10,
        );
        $api_url = 'http://api.tumblr.com/v2/blog/news.ponyvillelive.com/posts/photo?'.http_build_query($api_params);

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
                    }
                    elseif ($image['width'] == 1150 && $image['height'] == 200) // Older horizontal style.
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

                $local_path = DF_UPLOAD_FOLDER . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $local_path_base);
                $local_url = $local_path_base;

                if (!file_exists($local_path)) {
                    @copy($image_url, $local_path);

                    // Optimize image for fast display.
                    \DF\Image::resizeImage($local_path, $local_path, $image['width'], $image['height']);
                }

                $tags = array_map('strtolower', (array)$post['tags']);
                if (in_array('archive', $tags))
                    continue;

                $description = strip_tags($post['caption']);
                if (strpos($description, ':') === FALSE)
                    break;

                list($title, $description) = explode(':', $description, 2);
                $description = \DF\Utilities::truncateText($description, self::DESCRIPTION_LENGTH);

                $news_items[] = array(
                    'id' => 'tumblr_' . $post['id'],
                    'title' => trim($title),
                    'source' => 'tumblr',
                    'body' => trim($description),
                    'image_url' => $local_url,
                    'web_url' => $post['post_url'],
                    'layout' => $post_style,
                    'tags' => (array)$post['tags'],
                    'sort_timestamp' => $post['timestamp'],
                    'display_timestamp' => $post['timestamp'],
                );
            }
        }

        // Pull podcast episodes.
        $podcasts_raw = $em->createQuery('SELECT p, pe FROM Entity\Podcast p LEFT JOIN p.episodes pe
            WHERE (p.banner_url IS NOT NULL AND p.banner_url != \'\')
            AND (p.is_approved = 1)
            AND (pe.timestamp >= :threshold)
            ORDER BY p.id ASC, pe.timestamp DESC')
            ->setParameter('threshold', strtotime('-1 month'))
            ->getArrayResult();

        foreach((array)$podcasts_raw as $podcast)
        {
            foreach($podcast['episodes'] as $ep)
            {
                if (empty($ep['body']))
                    continue;

                $news_items[] = array(
                    'id' => 'podcast_' . $ep['guid'],
                    'title' => trim($ep['title']),
                    'source' => 'podcast',
                    'body' => $ep['summary'],
                    'image_url' => $podcast['banner_url'],
                    'web_url' => \Entity\PodcastEpisode::getEpisodeLocalUrl($ep, 'pvlnews'),
                    'layout' => 'vertical',
                    'tags' => array($podcast['name'], 'Podcast Episodes'),
                    'sort_timestamp' => $ep['timestamp'],
                    'display_timestamp' => $ep['timestamp'],
                );
                break;
            }
        }

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

        foreach($events_raw as $event)
        {
            $station_id = $event['station_id'];
            if (isset($promoted_stations[$station_id]))
                continue;
            else
                $promoted_stations[$station_id] = true;

            $range = Schedule::getRangeText($event['start_time'], $event['end_time'], $event['is_all_day']);

            $description = array();
            $description[] = 'Coming up on '.$event['station']['name'];
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
                'image_url' => $event['station']['banner_url'],
                'web_url' => $event['station']['web_url'],
                'layout' => 'vertical',
                'tags' => array($event['station']['name'], 'Events'),
                'sort_timestamp' => $sort_timestamp,
                'display_timestamp' => $event['start_time'],
            );
            break;
        }

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
}