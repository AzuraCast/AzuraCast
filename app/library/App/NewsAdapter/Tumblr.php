<?php
namespace App\NewsAdapter;

class Tumblr extends Rss
{
    public static function fetch($url, $params = array())
    {
        $url = str_replace('https:', 'http:', rtrim($url, '/')).'/rss';

        $latest_news = parent::fetch($url);

        if ($latest_news)
        {
            foreach($latest_news as &$news_row)
                $news_row['guid'] = str_replace('rss_', 'tumblr_', $news_row['guid']);
        }

        return $latest_news;
    }
}