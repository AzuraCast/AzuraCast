<?php
namespace PVL\NowPlayingAdapter;

use \Entity\Station;

class TheHiveRadio extends AdapterAbstract
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

		$return = @json_decode($return_raw, true);

		$np['listeners'] = $this->getListenerCount($return['uniquelisteners'], $return['currentlisteners']);
		$np['artist'] = $return['songartist'];
		$np['title'] = $return['song'];
		$np['text'] = $return['songtitle'];
		$np['is_live'] = false;

		return $np;
	}
}