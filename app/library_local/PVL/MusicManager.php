<?php
namespace PVL;

use \Entity\ArchiveSong;

require(DF_INCLUDE_LIB.'/ThirdParty/getid3/getid3.php');
require(DF_INCLUDE_LIB.'/ThirdParty/getid3/write.php');

class MusicManager
{
	const CHECK_DIR = '/www/mlpmusicarchive.com/music-to-add';
	const MUSIC_DIR = '/www/mlpmusicarchive.com/music';
	const ART_DIR = '/www/mlpmusicarchive.com/art';

	const MUSIC_URL = 'http://www.mlpmusicarchive.com/music';
	const ART_URL = 'http://www.mlpmusicarchive.com/art';

	public static function checkPendingFolder()
	{
		$pending_log = array();

		set_time_limit(600);
		$music_files = self::globDirectory(self::CHECK_DIR.'/*.mp3');

		$id3 = new \getID3;
		$id3->option_md5_data = true;
		$id3->option_md5_data_source = true;
		$id3->encoding = 'UTF-8';

		$existing_songs = ArchiveSong::getExistingSongHashes();

		foreach($music_files as $file_path)
		{
			set_time_limit(30);

			$file_info = $id3->analyze($file_path);
			$file_name = basename($file_path);

			if (isset($file_info['error']))
			{
				$pending_log['failures'][$file_name] = 'Error processing file: '.$file_info['error'];
				continue;
			}

			$song_info = array(
				'length'		=> $file_info['playtime_string'],
				'bitrate'		=> round($file_info['audio']['bitrate']/1000).'kbps',
				'title'			=> $file_info['tags']['id3v2']['title'][0],
				'artist'		=> $file_info['tags']['id3v2']['artist'][0],
				'album'			=> $file_info['tags']['id3v2']['album'][0],
				'year'			=> $file_info['tags']['id3v2']['year'][0],
				'genre'			=> $file_info['tags']['id3v2']['genre'][0],
			);

			$song_hash = ArchiveSong::getSongHash($song_info);
			if (in_array($song_hash, $existing_songs))
			{
				$pending_log['failures'][$file_name] = 'Duplicate song already exists.';
				continue;
			}

			$photo_data = $file_info['comments']['picture'][0]['data'];
			$image_path = $thumb_path = NULL;

			if ($photo_data)
			{
				$image_path = $file_path.'.jpg';
				$thumb_path = $file_path.'.thumb.jpg';

				try
				{
					@file_put_contents($image_path, $photo_data);
					@file_put_contents($thumb_path, $photo_data);

					\DF\Image::resizeImage($image_path, $thumb_path, 150, 150);
					@unlink($image_path);
				}
				catch(\Exception $e)
				{
					@unlink($image_path);
					@unlink($thumb_path);

					$image_path = $thumb_path = NULL;
				}
			}

			$song_info['file_path'] = $file_path;
			$song_info['art_path'] = $thumb_path;

			$song = new \Entity\ArchiveSong;
			$song->fromArray($song_info);
			$song->fixPaths(false);
			$song->save();

			$existing_songs[] = $song_hash;
			$pending_log['successes'][$file_name] = 'Imported successfully.';
		}

		set_time_limit(900);

		self::clearDirectory(self::CHECK_DIR, FALSE);
		self::cleanUpMainDirectories();

		return $pending_log;
	}

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

	/**
	 * Utility Functions
	 */

	// Recursively search a directory based on a pattern.
	public static function globDirectory($pattern, $flags = 0)
	{
		$files = (array)glob($pattern, $flags);
        
        foreach (glob(dirname($pattern).'/*', GLOB_ONLYDIR|GLOB_NOSORT) as $dir)
        {
            $files = array_merge($files, self::globDirectory($dir.'/'.basename($pattern), $flags));
        }
        
        return $files;
	}

	// Clear a directory and all of its contents.
	public static function clearDirectory($dir, $remove_current = true)
	{
		if (!file_exists($dir))
			return true; 
		if (!is_dir($dir) || is_link($dir))
			return unlink($dir); 

		foreach (scandir($dir) as $item)
		{ 
			if ($item == '.' || $item == '..')
				continue; 

			if (!self::clearDirectory($dir . "/" . $item))
			{ 
				@chmod($dir . "/" . $item, 0777); 
				if (!self::clearDirectory($dir . "/" . $item))
					return false; 
			}
		}

		if ($remove_current)
			return rmdir($dir);
	}

	// Remove any empty directories inside a folder structure.
	public static function cleanUpDirectory($dir, $remove_current = false)
	{
		if (!file_exists($dir))
			return true;

		foreach (scandir($dir) as $item)
		{
			if ($item == '.' || $item == '..')
				continue; 

			if (is_dir($dir.'/'.$item))
				self::cleanUpDirectory($dir."/".$item, true);
		}

		if ($remove_current)
			return @rmdir($dir);
	}

	public static function cleanUpMainDirectories()
	{
		self::cleanUpDirectory(self::MUSIC_DIR);
		self::cleanUpDirectory(self::ART_DIR);
	}

}