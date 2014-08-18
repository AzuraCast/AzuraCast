<?php
namespace PVL\NewsAdapter;

class Rss extends AdapterAbstract
{
    public static function fetch($feed_url, $params = array())
    {
        $http_client = \Zend_Feed::getHttpClient();
        $http_client->setConfig(array('timeout' => 10));

        try
        {
            $news_feed = new \Zend_Feed_Rss($feed_url);
        }
        catch(\Exception $e)
        {
            return array();
        }

        if (!is_null($news_feed))
        {
            $latest_news = array();
            $article_num = 0;
            
            foreach ($news_feed as $item)
            {
                $article_num++;

                $guid = $item->guid();
                
                // Process categories.
                $categories_raw = (is_array($item->category)) ? $item->category : array($item->category);
                $categories = array();
                
                foreach($categories_raw as $category)
                {
                    $categories[] = $category->__toString();
                }
            
                // Process main description.
                $description = trim($item->description()); // Remove extraneous tags.
                $description = str_replace(array("\r", "\n"), array('', ' '), $description); // Strip new lines.
                $description = preg_replace('/[^(\x20-\x7F)]+/',' ', $description); // Strip "exotic" non-ASCII characters.
                $description = preg_replace('/<a[^(>)]+>read more<\/a>/i', '', $description); // Remove "read more" link.

                $web_url = $item->link();
                if (is_array($web_url))
                    $web_url = $web_url[0];

                if (!$web_url && substr($guid, 0, 4) == 'http')
                    $web_url = $guid;
                
                $news_item = array(
                    'guid'          => 'rss_'.md5($guid),
                    'timestamp'     => strtotime($item->pubDate()),
                    'title'         => $item->title(),
                    'body'          => $description,
                    'web_url'       => $web_url,
                    'author'        => $item->author(),
                );

                \PVL\Debug::print_r($item);
                \PVL\Debug::print_r($news_item);
                
                $latest_news[] = $news_item;
            }

            return $latest_news;
        }
    }
}