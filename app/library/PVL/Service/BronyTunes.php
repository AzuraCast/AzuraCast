<?php
namespace PVL\Service;

use Entity\Song;
use Entity\SongExternalBronyTunes;
use Entity\SongExternalBronyTunes as External;

class BronyTunes
{
    public static function load($force = false)
    {
        set_time_limit(180);

        // Get existing IDs to avoid unnecessary work.
        $existing_ids = External::getIds();
        $song_ids = Song::getIds();

        $remote_url = 'https://bronytunes.com/retrieve_songs.php?client_type=ponyvillelive';
        $result_raw = @file_get_contents($remote_url);

        $em = External::getEntityManager();

        if ($result_raw)
        {
            $result = json_decode($result_raw, TRUE);

            $i = 1;
            foreach((array)$result as $row)
            {
                $id = $row['song_id'];
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

                if ($i % 300 == 0)
                {
                    $em->flush();
                    $em->clear();
                }

                $i++;
            }

            $em->flush();
            $em->clear();

            return true;
        }

        return false;
    }
}