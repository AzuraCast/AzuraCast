<?php
namespace PVL\NewsAdapter;

class Tumblr extends AdapterAbstract
{
    public static function fetch($url, $params = array())
    {
        $news_items = array();

        $url = str_replace('https:', 'http:', rtrim($url, '/')).'/rss';

        $http_client = \Zend_Feed::getHttpClient();
        $http_client->setConfig(array('timeout' => 10));

        try
        {
            $news_feed = new \Zend_Feed_Rss($url);
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
                
                // Process categories.
                $categories_raw = (is_array($item->category)) ? $item->category : array($item->category);
                $categories = array();
                
                foreach($categories_raw as $category)
                {
                    $categories[] = $category->__toString();
                }
            
                // Process main description.
                $description = trim($item->description());
                
                $news_item = array(
                    'guid'          => 'tumblr_'.md5($item->guid),
                    'timestamp'     => strtotime($item->pubDate()),
                    'title'         => $item->title(),
                    'body'          => $description,
                    'web_url'       => $item->link(),
                    'author'        => $item->author(),
                );
                
                $latest_news[] = $news_item;
            }

            return $latest_news;
        }
    }
}