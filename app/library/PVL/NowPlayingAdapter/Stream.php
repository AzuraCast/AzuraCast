<?php
namespace PVL\NowPlayingAdapter;

use \Entity\Station;

class Stream extends AdapterAbstract
{
	/* Process a nowplaying record. */
	protected function _process($np)
	{
		$is_live = false;

		if (stristr($this->url, 'livestream') !== FALSE)
		{
			$xml = $this->getUrl($this->url, 30);

			if ($xml)
				$stream_data = \DF\Export::XmlToArray($xml);
			else
				$stream_data = NULL;

			if ($stream_data)
			{
				$np['listeners'] = (int)$stream_data['channel']['ls:currentViewerCount'];

				if ($stream_data['channel']['ls:isLive'] && $stream_data['channel']['ls:isLive'] == 'true')
				{
					$is_live = true;
					$np['is_live'] = 'true';
					$np['text'] = 'Stream Online';
					return $np;
				}
			}
		}
		else if (stristr($this->url, 'twitch.tv') !== FALSE)
		{
			$return_raw = $this->getUrl();

			if ($return_raw)
			{
				$return = json_decode($return_raw, true);
				$stream = $return['stream'];

				if ($stream)
				{
					$is_live = true;
					$np['title'] = $stream['game'];
					$np['artist'] = 'Stream Online';
					$np['text'] = 'Stream Online';
					$np['listeners'] = (int)$stream['viewers'];
					$np['is_live'] = 'true';
					return $np;
				}
			}
		}
		else if (stristr($this->url, 'justin.tv') !== FALSE)
		{
			$return_raw = $this->getUrl();

			if ($return_raw)
			{
				$return = json_decode($return_raw, true);
				$stream = $return[0];

				if ($stream)
				{
					$is_live = true;
					$np['title'] = $stream['title'];
					$np['artist'] = 'Stream Online';
					$np['text'] = 'Stream Online';
					$np['listeners'] = (int)$stream['stream_count'];
					$np['is_live'] = 'true';
					return $np;
				}
			}
		}

		return false;
	}
}