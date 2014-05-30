<?php
namespace PVL\Service;

use \Entity\Song;

class PonyFm
{
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