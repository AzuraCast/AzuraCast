<?php
namespace PVL\Service;

use \Entity\Song;
use \Entity\SongExternalBronyTunes;

class BronyTunes
{
	public static function load()
	{
		set_time_limit(180);

		// Get existing IDs to avoid unnecessary work.
		$existing_ids = SongExternalBronyTunes::getIds();

		$remote_url = 'https://bronytunes.com/retrieve_songs.php?client_type=ponyvillelive';
		$result_raw = @file_get_contents($remote_url);

		$em = SongExternalBronyTunes::getEntityManager();

		if ($result_raw)
		{
			$result = json_decode($result_raw, TRUE);

			$i = 1;
			foreach((array)$result as $row)
			{
				$id = $row['song_id'];
				$processed = SongExternalBronyTunes::processRemote($row);

				if (isset($existing_ids[$id]))
				{
					if ($existing_ids[$id] != $processed['hash'])
						$record = SongExternalBronyTunes::find($id);
					else
						$record = NULL;
				}
				else
				{
					$record = new SongExternalBronyTunes;
				}

				if ($record instanceof SongExternalBronyTunes)
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