<?php
namespace PVL\Service;

use Entity\Song;
use Entity\SongExternalEqBeats as External;

use PVL\Debug;

class EqBeats
{
    public static function load($force = false)
    {
        set_time_limit(300);

        Debug::startTimer('Load remote data');

        $new_songs = array();

        for($i = 1; $i <= 200; $i++)
        {
            $page_tracks = self::loadPage($i);

            if (count($page_tracks) == 0)
                break;

            foreach ((array)$page_tracks as $row)
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
        $remote_url = 'https://eqbeats.org/tracks/all/json?'.http_build_query(array(
            'page'      => $page,
            'per_page'  => 100,
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
     * Single Record Search
     */

    protected static function _exactSearch($song)
    {
        $base_url = 'https://eqbeats.org/tracks/search/exact/json';
        $url = $base_url.'?'.http_build_query(array(
            'artist'    => $song->artist,
            'track'     => $song->title,
            'client'    => 'ponyvillelive',
        ));

        Debug::log('Exact Search: '.$url);

        $result = file_get_contents($url);
        if ($result)
        {
            $rows = json_decode($result, TRUE);

            if (count($rows) > 0)
                return $rows[0];
        }

        return NULL;
    }

    protected static function _querySearch($song)
    {
        $base_url = 'https://eqbeats.org/tracks/search/json';
        $url = $base_url.'?'.http_build_query(array(
            'q'         => $song->artist.' '.$song->title,
            'client'    => 'ponyvillelive',
        ));

        Debug::log('Query Search: '.$url);

        $result = file_get_contents($url);
        if ($result)
        {
            $rows = json_decode($result, TRUE);

            foreach($rows as $row)
            {
                $song_hash = Song::getSongHash(array(
                    'artist'    => $row['user']['name'],
                    'title'     => $row['title'],
                ));

                if (strcmp($song_hash, $song->id) == 0)
                    return $row;
            }
        }

        return NULL;
    }
}