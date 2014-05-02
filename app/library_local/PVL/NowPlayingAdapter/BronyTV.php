<?php
namespace PVL\NowPlayingAdapter;

use \Entity\Station;

class BronyTV extends AdapterAbstract
{
	/* Process a nowplaying record. */
	public function process(&$np)
	{
		$return_raw = $this->getUrl();

		if ($return_raw)
		{
			$return = @json_decode($return_raw, TRUE);
			$return = $return[0];

			$np['listeners'] = (int)$return['Total_Viewers'];

			if ($return['Stream_Status'] == 'Stream is offline')
			{
				$np['text'] = 'Stream Offline';
				$np['is_live'] = 'false';
			}
			else
			{
				$parts = explode("-", str_replace('|', '-', $return['Stream_Status']), 2);
				$parts = array_map(function($x) { return trim($x); }, (array)$parts);

				$np['artist'] = $parts[0];
				$np['title'] = $parts[1];
				$np['text'] = implode(' - ', $parts);
				$np['is_live'] = 'true';
			}
		}
		else
		{
			$np['artist'] = 'Offline';
			$np['title'] = 'Offline';
			$np['text'] = 'Stream Offline';
			$np['is_live'] = 'false';
		}

		return $np;
	}
}