<?php
namespace PVL;

use \Entity\NetworkNews;

class NewsManager
{
    public static function syncNetwork()
    {
        $di = \Phalcon\Di::getDefault();
        $em = $di->get('em');
        $config = $di->get('config');

        // Pull featured images.
        $timestamp_threshold = strtotime('-6 weeks');

        $api_params = array(
            'api_key'       => $config->apis->tumblr->key,
            'limit'         => 10,
        );
        $api_url = 'http://api.tumblr.com/v2/blog/news.ponyvillelive.com/posts/photo?'.http_build_query($api_params);

        $results_raw = @file_get_contents($api_url);
        $news_items = array();

        if ($results_raw)
        {
            $results = json_decode($results_raw, true);
            $posts = $results['response']['posts'];

            foreach((array)$posts as $post)
            {
                if ($post['timestamp'] < $timestamp_threshold)
                    continue;

                $image = null;
                $post_style = 'vertical';
                $image_is_valid = false;

                foreach((array)$post['photos'] as $photo)
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

                $local_path_base = 'rotators/'.$image_url_basename;

                $local_path = DF_UPLOAD_FOLDER.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $local_path_base);
                $local_url = $local_path_base;

                if (!file_exists($local_path))
                {
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
                $description = \DF\Utilities::truncateText($description, 300);

                $news_items[] = array(
                    'id'        => 'tumblr_'.$post['id'],
                    'title'     => trim($title),
                    'body'      => trim($description),
                    'image_url' => $local_url,
                    'web_url'   => $post['post_url'],
                    'layout'    => $post_style,
                    'tags'      => (array)$post['tags'],
                    'timestamp' => $post['timestamp'],
                );
            }

            \PVL\Debug::print_r($news_items);

            // Delete current rotator contents.
            $em->createQuery('DELETE FROM Entity\NetworkNews nn')->execute();

            foreach($news_items as $item)
            {
                $record = new NetworkNews;
                $record->fromArray($item);

                $em->persist($record);
            }

            $em->flush();

            // Flush cache of homepage news.
            \DF\Cache::remove('homepage_featured_news');
        }
    }
}