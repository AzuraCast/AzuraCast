<?php
namespace PVL\NowPlayingAdapter;

use \Entity\Station;

class CelestiaRadio extends AdapterAbstract
{
	/* Process a nowplaying record. */
	public function process(&$np)
	{
		$return_raw = $this->getUrl();

		if (!$return_raw)
		{
			$np['text'] = 'Stream Offline';
			$np['is_live'] = 'false';
			return;
		}

		$return = json_decode($return_raw, true);

		if (isset($return['result']))
		{
			$return = $return['result'];

			$np['listeners'] = $this->getListenerCount($return['listeners']['unique'], $return['listeners']['current']);
			$np['artist'] = $return['current_song']['song']['artist'];
			$np['title'] = $return['current_song']['song']['title'];
			$np['text'] = $return['current_song']['song']['text'];
		}
		
		return $np;
	}
}