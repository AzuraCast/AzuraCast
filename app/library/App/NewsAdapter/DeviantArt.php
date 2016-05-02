<?php
namespace App\NewsAdapter;

class DeviantArt extends AdapterAbstract
{
    public static function getAccount($url)
    {
        if (stristr($url, 'deviantart.com') !== FALSE)
        {
            $url = str_replace(array('http://', 'https://', '//'), array('', '', ''), $url);
            return substr($url, 0, strpos($url, '.'));
        }
        else
        {
            return trim($url);
        }
    }

    public static function fetch($url, $params = array())
    {
        $news_items = array();
        $author = self::getAccount($url);

        $http_params = array(
            'type'      => 'deviation',
            'q'         => 'by:'.$author.' sort:time meta:all',
        );
        $feed_url = 'http://backend.deviantart.com/rss.xml?'.http_build_query($http_params);

        $news_feed = @file_get_contents($feed_url);

        if ($news_feed)
        {
            $article_num = 0;

            $feed_array = \DF\Export::XmlToArray($news_feed);
            $items = $feed_array['rss']['channel'][0]['item'];
            
            foreach ((array)$items as $item)
            {
                if (!isset($item['media:thumbnail']))
                    continue;

                $news_items[] = array(
                    'guid'          => 'deviantart_'.md5($item['link']),
                    'media_format'  => 'image',
                    'timestamp'     => strtotime($item['pubDate']),
                    'title'         => $item['title'],
                    'body'          => $item['description'],
                    'web_url'       => $item['link'],
                );
            }
        }

        return $news_items;
    }
}