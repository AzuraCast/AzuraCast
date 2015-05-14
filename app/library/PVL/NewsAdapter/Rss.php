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

                $title = $item->title();

                if (is_array($title))
                    $title = $title[0]->nodeValue;
                
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
                $description = preg_replace('/<a[^(>)]+>read more<\/a>/iu', '', $description); // Remove "read more" link.

                $web_url = $item->link();
                if (is_array($web_url))
                    $web_url = $web_url[0];

                if ($web_url instanceof \DOMElement)
                    $web_url = $web_url->nodeValue;

                if (!$web_url && substr($guid, 0, 4) == 'http')
                    $web_url = $guid;

                $author = $item->author();
                if (is_array($author))
                    $author = $author[0]->nodeValue;
                
                $news_item = array(
                    'guid'          => 'rss_'.md5($guid),
                    'timestamp'     => strtotime($item->pubDate()),
                    'media_format'  => 'mixed',
                    'title'         => $title,
                    'body'          => $description,
                    'web_url'       => $web_url,
                    'author'        => $author,
                );

                \PVL\Debug::print_r($news_item);
                
                $latest_news[] = $news_item;
            }

            return $latest_news;
        }
    }
}