<?php
namespace PVL\NewsAdapter;

use Zend\Feed\Reader\Reader;

class Rss extends AdapterAbstract
{
    public static function fetch($feed_url, $params = array())
    {
        try
        {
            $news_feed = Reader::import($feed_url);
        }
        catch(\Exception $e)
        {
            return array();
        }

        if (is_null($news_feed))
            return array();

        $latest_news = array();
        $article_num = 0;

        foreach ($news_feed as $item)
        {
            $article_num++;

            $guid = $item->getId();
            $title = $item->getTitle();

            // Process categories.
            $categories_raw = $item->getCategories()->getValues();

            // Process main description.
            $description = trim($item->getDescription()); // Remove extraneous tags.
            $description = str_replace(array("\r", "\n"), array('', ' '), $description); // Strip new lines.
            $description = preg_replace('/<a[^(>)]+>read more<\/a>/iu', '', $description); // Remove "read more" link.

            $web_url = $item->getLink();
            if (is_array($web_url))
                $web_url = $web_url[0];

            if (!$web_url && substr($guid, 0, 4) == 'http')
                $web_url = $guid;

            $author = $item->getAuthor();
            if (is_array($author))
                $author = $author[0]->nodeValue;

            $news_item = array(
                'guid'          => 'rss_'.md5($guid),
                'timestamp'     => $item->getDateModified()->getTimestamp(),
                'media_format'  => 'mixed',
                'title'         => $title,
                'body'          => $description,
                'web_url'       => $web_url,
                'author'        => $author,
            );

            $latest_news[] = $news_item;
        }

        return $latest_news;
    }
}