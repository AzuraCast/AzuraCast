<?php
namespace PVL\NowPlayingAdapter;

use \Entity\Station;

class IceCast extends AdapterAbstract
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

		$temp_array = array();
		$search_for = "<td\s[^>]*class=\"streamdata\">(.*)<\/td>";
		$search_td = array('<td class="streamdata">','</td>');

		if(preg_match_all("/$search_for/siU", $return_raw, $matches)) 
		{
			foreach($matches[0] as $match) 
			{
				$to_push = str_replace($search_td,'',$match);
				$to_push = trim($to_push);
				array_push($temp_array,$to_push);
			}
		}

		// In the case of multiple streams, always use the last stream.
		if (count($temp_array) > 12)
			$temp_array = array_slice($temp_array, -10);

		list($artist, $track) = explode(" - ",$temp_array[9], 2);

		$np['listeners'] = (int)$temp_array[5];
		$np['artist'] = $artist;
		$np['title'] = $track;
		$np['text'] = $temp_array[9];
		$np['is_live'] = 'false';
		return;
	}
}