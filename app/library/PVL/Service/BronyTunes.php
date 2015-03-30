<?php
namespace PVL\Service;

use Entity\Song;
use Entity\SongExternalBronyTunes as External;

use PVL\Debug;

class BronyTunes
{
    public static function load($force = false)
    {
        set_time_limit(300);

        Debug::startTimer('Load remote data');

        $remote_url = 'https://bronytunes.com/retrieve_songs.php?client_type=ponyvillelive';
        $result_raw = @file_get_contents($remote_url);

        Debug::endTimer('Load remote data');

        if ($result_raw)
        {
            $result = json_decode($result_raw, TRUE);

            $new_songs = array();
            foreach ((array)$result as $row)
            {
                $processed = External::processRemote($row);
                $processed['hash'] = Song::getSongHash($processed);

                $new_songs[$processed['hash']] = $processed;
            }

            return External::import($new_songs, $force);
        }

        return false;
    }
}