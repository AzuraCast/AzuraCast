<?php
namespace PVL\NowPlayingAdapter;

use \Entity\Station;

class ShoutCast2 extends AdapterAbstract
{
	/* Process a nowplaying record. */
	protected function _process($np)
	{
		$return_raw = $this->getUrl();

		if ($return_raw)
		{
			$current_data = \DF\Export::XmlToArray($return_raw);
			$song_data = $current_data['SHOUTCASTSERVER'];

			$title_parts = explode('-', str_replace('   ', ' - ', $song_data['SONGTITLE']), 2);
			$artist = trim(array_shift($title_parts));
			$title = trim(implode('-', $title_parts));

			$np['title'] = $title;
			$np['artist'] = $artist;
			$np['text'] = $song_data['SONGTITLE'];

			$np['listeners_unique'] = (int)$song_data['UNIQUELISTENERS'];
			$np['listeners_total'] = (int)$song_data['CURRENTLISTENERS'];
			$np['listeners'] = $this->getListenerCount($np['listeners_unique'], $np['listeners_total']);

			$np['is_live'] = 'false'; // ($song_data['NEXTTITLE'] != '') ? 'false' : 'true';
			return $np;
		}

		return false;
	}
}