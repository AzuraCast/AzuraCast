<?php
namespace PVL\Service;

use \Entity\Song;
use \Entity\SongExternalEqBeats;
use \Entity\SongExternalEqBeats as External;

class EqBeats
{
    public static function load($force = false)
    {
        set_time_limit(300);

        // Get existing IDs to avoid unnecessary work.
        $existing_ids = External::getIds();
        $song_ids = Song::getIds();

        $em = External::getEntityManager();

        $tracks = array();

        for($i = 1; $i <= 200; $i++)
        {
            $page_tracks = self::loadPage($i);

            if (count($page_tracks) == 0)
                break;

            $tracks[$i] = $page_tracks;
        }

        // Loop through tracks.
        foreach($tracks as $page_num => $result)
        {
            foreach((array)$result as $row)
            {
                $id = $row['id'];
                $processed = External::processRemote($row);

                $processed['hash'] = Song::getSongHash($processed);
                if (!in_array($processed['hash'], $song_ids))
                    Song::getOrCreate($processed);

                if (isset($existing_ids[$id]))
                {
                    if ($existing_ids[$id] != $processed['hash'] || $force)
                        $record = External::find($id);
                    else
                        $record = NULL;
                }
                else
                {
                    $record = new External;
                }

                if ($record instanceof External)
                {
                    $record->fromArray($processed);
                    $em->persist($record);
                }
            }

            $em->flush();
            $em->clear();
        }

        return true;
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

    public static function fetch(Song $song)
    {
        $result = self::_exactSearch($song);

        if (!$result)
            $result = self::_querySearch($song);

        \PVL\Debug::print_r($result);

        if ($result)
            return $result;
        else
            return NULL;
    }

    protected static function _exactSearch($song)
    {
        $base_url = 'https://eqbeats.org/tracks/search/exact/json';
        $url = $base_url.'?'.http_build_query(array(
            'artist'    => $song->artist,
            'track'     => $song->title,
            'client'    => 'ponyvillelive',
        ));

        \PVL\Debug::log('Exact Search: '.$url);

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

        \PVL\Debug::log('Query Search: '.$url);

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