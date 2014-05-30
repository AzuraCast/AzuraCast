<?php
namespace PVL\Service;

use \Entity\Song;

class EqBeats
{
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
			'artist'	=> $song->artist,
			'track'		=> $song->title,
			'client'	=> 'ponyvillelive',
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
			'q'			=> $song->artist.' '.$song->title,
			'client'	=> 'ponyvillelive',
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