<?php
namespace PVL\NowPlayingAdapter;

use \Entity\Station;

class RadioBrony extends AdapterAbstract
{
	/* Process a nowplaying record. */
	protected function _process($np)
	{
		$return_raw = $this->getUrl();

		if (!$return_raw)
			return false;

		$return = json_decode($return_raw, true);

		$np['listeners'] = (int)$return['listeners'];
		$np['artist'] = $return['now_playing']['artist'];

		if ($return['now_playing']['track'])
			$np['title'] = $return['now_playing']['track'];
		else
			$np['title'] = $return['now_playing']['song'];

		$np['text'] = $return['now_playing']['song'];

		$np['is_live'] = ($return['mount'] != '/autodj');
		
		return $np;
	}
}