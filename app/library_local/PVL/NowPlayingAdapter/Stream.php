<?php
namespace PVL\NowPlayingAdapter;

use \Entity\Station;

class Stream extends AdapterAbstract
{
	/* Process a nowplaying record. */
	public function process(&$np)
	{
		if (stristr($this->url, 'livestream') !== FALSE)
		{
			$xml = $this->getUrl($this->url, 30);

			if ($xml)
			{
				$stream_data = \DF\Export::XmlToArray($xml);
			}

			if ($stream_data)
			{
				$np['listeners'] = (int)$stream_data['channel']['ls:currentViewerCount'];

				if ($stream_data['channel']['ls:isLive'] && $stream_data['channel']['ls:isLive'] == 'true')
				{
					$np['is_live'] = 'true';
					$np['text'] = 'Stream Online';
				}
				else
				{
					$np['is_live'] = 'false';
					$np['text'] = 'Stream Offline';
				}
			}
		}
		else if (stristr($this->url, 'twitch.tv') !== FALSE)
		{
			$return_raw = $this->getUrl();

			$is_live = false;
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
				}
			}

			if (!$is_live)
			{
				$np['text'] = 'Stream Offline';
				$np['is_live'] = 'false';
			}
		}
		else if (stristr($this->url, 'justin.tv') !== FALSE)
		{
			$return_raw = $this->getUrl();

			$is_live = false;
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
				}
			}

			if (!$is_live)
			{
				$np['text'] = 'Stream Offline';
				$np['is_live'] = 'false';
			}
		}

		return $np;
	}
}