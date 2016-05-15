<?php
namespace App\Sync;

use Entity\Station;
use Entity\StationMedia;

class Media
{
    public static function sync()
    {
        $stations = Station::fetchAll();
        foreach($stations as $station)
            self::processStation($station);
    }

    public static function processStation(Station $station)
    {
        $base_dir = $station->getRadioMediaDir();
        if (empty($base_dir))
            return false;

        $music_files_raw = self::globDirectory($base_dir.'/*.mp3');
        $music_files = array();

        foreach($music_files_raw as $music_file_path)
        {
            $path_short = str_replace($base_dir.'/', '', $music_file_path);
            $path_hash = md5($path_short);
            $music_files[$path_hash] = $path_short;
        }
        
        $di = \Phalcon\Di::getDefault();
        $em = $di->get('em');

        $existing_media = $station->media;
        foreach($existing_media as $media_row)
        {
            // Check if media file still exists.
            $full_path = $base_dir.'/'.$media_row->path;
            
            if (file_exists($full_path))
            {
                // Check for modifications.
                $media_row->loadFromFile();
                $em->persist($media_row);

                $path_hash = md5($media_row->path);
                unset($music_files[$path_hash]);
            }
            else
            {
                // Delete the now-nonexistent media item.
                $em->remove($media_row);
            }
        }

        // Create files that do not currently exist.
        foreach($music_files as $new_file_path)
        {
            $record = new StationMedia;
            $record->station = $station;
            $record->path = $new_file_path;

            $record->loadFromFile();

            $em->persist($record);
        }

        $em->flush();
    }

    public static function globDirectory($pattern, $flags = 0)
    {
        $files = (array)glob($pattern, $flags);
        foreach (glob(dirname($pattern).'/*', GLOB_ONLYDIR|GLOB_NOSORT) as $dir)
            $files = array_merge($files, self::globDirectory($dir.'/'.basename($pattern), $flags));

        return $files;
    }

    /*
    public static function writeSongData(ArchiveSong $song)
    {
        $getID3 = new \getID3;
        $getID3->setOption(array('encoding'=> 'UTF8'));

        $tagwriter = new \getid3_writetags;
        $tagwriter->filename = $song->file_path;

        $tagwriter->tagformats = array('id3v1', 'id3v2.3');
        $tagwriter->overwrite_tags = true;
        $tagwriter->tag_encoding = 'UTF8';
        $tagwriter->remove_other_tags = true;

        $tag_data = array(
            'title'         => array($song->title),
            'artist'        => array($song->artist),
            'album'         => array($song->album),
            'year'          => array($song->year),
            'genre'         => array($song->genre),
            'track'         => array($song->track_number),
        );

        $art_path = $song->art_path;
        if ($art_path)
        {
            if ($fd = fopen($art_path, 'rb'))
            {
                $APICdata = fread($fd, filesize($art_path));
                fclose($fd);

                list($APIC_width, $APIC_height, $APIC_imageTypeID) = getimagesize($art_path);
                $imagetypes = array(1=>'gif', 2=>'jpeg', 3=>'png');

                if (isset($imagetypes[$APIC_imageTypeID]))
                {
                    $tag_data['attached_picture'][0]['data']          = $APICdata;
                    $tag_data['attached_picture'][0]['picturetypeid'] = $APIC_imageTypeID;
                    $tag_data['attached_picture'][0]['description']   = basename($art_path);
                    $tag_data['attached_picture'][0]['mime']          = 'image/'.$imagetypes[$APIC_imageTypeID];

                    $tag_data['comments']['picture'][0] = $tag_data['attached_picture'][0];
                }
            }
        }

        $tagwriter->tag_data = $tag_data;

        // write tags
        if ($tagwriter->WriteTags())
        {
            return true;
        }
        else
        {
            throw new \Exception(implode('<br><br>', $tagwriter->errors));
            return false;
        }
    }
    */
}