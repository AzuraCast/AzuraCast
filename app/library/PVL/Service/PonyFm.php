<?php
namespace PVL\Service;

use Entity\Song;
use Entity\SongExternalPonyFm as External;

use PVL\Debug;

class PonyFm
{
    public static function load($force = false)
    {
        set_time_limit(300);

        Debug::startTimer('Load remote data');

        $tracks = array();
        $first_page = self::loadPage(1);

        if (!$first_page)
            return FALSE;

        $total_pages = (int)$first_page['total_pages'];
        $tracks[1] = $first_page['tracks'];

        for($i = 2; $i <= $total_pages; $i++)
        {
            $next_page = self::loadPage($i);
            if ($next_page)
                $tracks[$i] = $next_page['tracks'];
        }

        // Loop through tracks.
        $new_songs = array();

        foreach($tracks as $page_num => $result)
        {
            foreach ((array)$result as $row)
            {
                $processed = External::processRemote($row);
                $processed['hash'] = Song::getSongHash($processed);

                $new_songs[$processed['hash']] = $processed;
            }
        }

        Debug::endTimer('Load remote data');

        return External::import($new_songs, $force);
    }

    public static function loadPage($page = 1)
    {
        $remote_url = 'https://pony.fm/api/web/tracks?'.http_build_query(array(
            'page'      => $page,
            'client'    => 'ponyvillelive',
        ));
        
        $result_raw = @file_get_contents($remote_url);

        if ($result_raw)
        {
            $result = json_decode($result_raw, TRUE);
            return $result;
        }

        return NULL;
    }

    /**
     * Individual Record Fetching (Retired)
     */

    public static function fetch(Song $song)
    {
        $base_url = 'https://pony.fm/api/v1/tracks/radio-details/';
        $song_hash = self::_getHash($song);

        $url = $base_url.$song_hash.'?client=ponyvillelive';
        \PVL\Debug::log('Hash Search: '.$url);

        $result_raw = @file_get_contents($url);

        if ($result_raw)
        {
            $result = json_decode($result_raw, TRUE);

            \PVL\Debug::print_r($result);

            return $result;
        }

        return NULL;
    }

    protected static function _getHash($song)
    {
        if ($song->artist)
        {
            $song_artist = $song->artist;
            $song_title = $song->title;
        }
        else
        {
            list($song_artist, $song_title) = explode('-', $song->text);
        }

        return md5(self::_sanitize($song_artist).' - '.self::_sanitize($song_title));
    }

    protected static function _sanitize($value)
    {
        $value = preg_replace('/[^A-Za-z0-9]/', '', $value);
        return strtolower($value);
    }
}