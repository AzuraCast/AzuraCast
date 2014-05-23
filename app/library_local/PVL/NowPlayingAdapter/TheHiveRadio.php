<?php
namespace PVL\NowPlayingAdapter;

use \Entity\Station;

class TheHiveRadio extends AdapterAbstract
{
	/* Process a nowplaying record. */
	protected function _process($np)
	{
		$return_raw = $this->getUrl();

		if (!$return_raw)
			return false;

		$return = @json_decode($return_raw, true);

		if ($return['server_ver'])
		{
			$np['listeners'] = (int)$return['server_listener_total'];

			$np_stream = $return['server_streams']['normal.mp3'];
			$np['artist'] = $np_stream['artist'];
			$np['title'] = $np_stream['song'];
			$np['text'] = $np_stream['artist'].' - '.$np_stream['song'];
			return $np;
		}

		return false;
	}
}