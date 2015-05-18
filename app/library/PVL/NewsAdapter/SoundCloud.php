<?php
namespace PVL\NewsAdapter;

class SoundCloud extends AdapterAbstract
{
    public static function fetch($url, $params = array())
    {
        if (empty($url))
            return null;

        // Load SoundCloud config from API.
        $di = self::getDi();
        $config = $di->get('config');
        $sc_config = $config->apis->soundcloud->toArray();

        if (!$sc_config)
            return false;

        $soundcloud = new \Soundcloud\Service($sc_config['client_id'], $sc_config['client_secret']);

        $resolve_raw = $soundcloud->get('resolve', array('url' => $url));
        if (empty($resolve_raw))
            return false;

        $resolve = json_decode($resolve_raw, true);
        $tracks = array();

        switch($resolve['kind'])
        {
            case 'user':
                $uid = $resolve['id'];
                $tracks_raw = $soundcloud->get('users/'.$uid.'/tracks');

                if ($tracks_raw)
                    $tracks = json_decode($tracks_raw, true);
                break;

            case 'playlist':
                $tracks = $resolve['tracks'];
                break;
        }

        if ($tracks)
        {
            $records = array();

            foreach($tracks as $track)
            {
                $row = array(
                    'guid'          => 'soundcloud_'.$track['id'],
                    'timestamp'     => strtotime($track['last_modified']),
                    'media_format'  => 'audio',
                    'title'         => $track['title'],
                    'body'          => $track['description'],
                    'web_url'       => $track['permalink_url'],
                    'author'        => $track['user']['username'],
                );
                $records[] = $row;
            }

            return $records;
        }
    }
}