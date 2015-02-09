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

        $records_raw = parent::fetch($feed_url, $params);

        $records = array();
        foreach((array)$records_raw as $row)
        {
            $row['media_format'] = 'audio';
            $row['body'] = str_replace('SoundCloud conversion to RSS provided by Cloud Flipper: [Web] - [Facebook] - [Donate!]', '', $row['body']);

            $records[] = $row;
        }

        return $records;
    }
}