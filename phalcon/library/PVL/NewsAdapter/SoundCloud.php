<?php
namespace PVL\NewsAdapter;

class SoundCloud extends Rss
{
    public static function fetch($url, $params = array())
    {
        if (empty($url))
            return null;

        // Use a third-party service to convert Soundcloud URLs into RSS feed URLs.
        $feed_url = 'http://picklemonkey.net/cloudflipper/cloudflipper.php?'.http_build_query(array(
            'feed' => $url,
        ));

        return parent::fetch($feed_url, $params);
    }
}