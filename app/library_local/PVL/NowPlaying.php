<?php
namespace PVL;

use \Entity\Statistic;
use \Entity\Schedule;
use \Entity\Station;
use \Entity\Song;

class NowPlaying
{
	public static function get($version = 1, $id = NULL)
	{
		$raw_data = @file_get_contents(self::getFilePath());

		if ($raw_data)
		{
			$nowplaying = @json_decode($raw_data, true);

		    if ($version == 1)
		    {
		    	$nowplaying_new = array();
		    	foreach($nowplaying as $shortname => $info)
		    	{
		    		$nowplaying_new[$info['id']] = array(
		    			'name' => $info['name'],
		    			'listeners' => $info['listeners'],
		    			'title' => $info['title'],
		    			'artist' => $info['artist'],
		    			'text' => $info['text'],
		    		);
		    	}
		    	$nowplaying = $nowplaying_new;
		    }

		    if ($id)
		    	return $nowplaying[$id];
		    else
		    	return $nowplaying;
		}

		return null;
	}

	public static function getFilePath($file_base = 'nowplaying', $file_ext = 'json')
	{
		return DF_INCLUDE_STATIC.'/api/'.$file_base.'.'.$file_ext;
	}

	public static function generate()
	{
		set_time_limit(60);

		// Generate PVL now playing file.
		$pvl_file_path = self::getFilePath('nowplaying');

		$nowplaying = self::loadNowPlaying();
		$nowplaying_feed = json_encode($nowplaying);
		@file_put_contents($pvl_file_path, $nowplaying_feed);

		// Write shorter delimited file.
		$text_file_path = self::getFilePath('nowplaying', 'txt');
		$text_lines = array();

		foreach($nowplaying as $station_shortcode => $station_info)
		{
			if ($station_info['category'] == 'audio')
			{
				$text_line = array(
					$station_info['id'],
					$station_info['name'],
					$station_info['listeners'],
					$station_info['title'],
					$station_info['artist'],
				);
				$text_lines[] = implode('|', $text_line);
			}
		}

		$nowplaying_text = implode("<>", $text_lines);
		@file_put_contents($text_file_path, $nowplaying_text);

		// Post statistics to official record.
		Statistic::post($nowplaying);

		/*
		// Generate CR now playing file.
		$cr_file_path = self::getFilePath('cr');

		$nowplaying['cr'] = self::processStation(array(
			'id'		=> 0,
			'category'	=> 'audio',
			'type'		=> 'centovacast',
			'code'		=> 'CR',
			'name'		=> 'Celestia Radio',
			'acronym'	=> 'CR',
			'genre'		=> '"All pony, all the time!"',
			'image_url'	=> 'stations/cr.png',
			'web_url'	=> 'http://www.ponify.me/',
			'stream_url' => 'http://molestia.ponify.me:8062/stream',
			'nowplaying_url' => 'http://ponify.me/stats.php',
		));
		$nowplaying['cr']['player_url'] = 'http://ponify.me/player.html';

		$nowplaying_feed = json_encode($nowplaying);
		@file_put_contents($cr_file_path, $nowplaying_feed);
		*/

		return $pvl_file_path;
	}

	public static function loadNowPlaying()
	{
		$overall_start = time();
		$em = \Zend_Registry::get('em');

		$stations = $em->createQuery('SELECT s.id FROM Entity\Station s WHERE s.is_active = 1 ORDER BY s.category ASC, s.weight ASC')
			->getArrayResult();

		$nowplaying = array();

    	foreach($stations as $station_info)
    	{
    		$station = Station::find($station_info['id']);
    		
    		$start_time = time();

    		$np = self::processStation($station);
    		// self::getNowPlayingImage($np, $station);

    		$end_time = time();
    		$time_taken = $end_time - $start_time;
    		echo "\n".'Station "'.$station->name.'" processed in '.$time_taken.' seconds.';

    		$name = $station->short_name;
    		$nowplaying[$name] = $np;
    		
    		$em->persist($station);
    		$em->flush();
    		$em->clear();
    	}

    	$overall_end = time();
    	$overall_total = $overall_end - $overall_start;
    	echo "\nNow Playing finished in ".$overall_total." seconds.";

    	return $nowplaying;
	}

	public static function processStation(Station $station)
	{
		$curl = curl_init();

		$current_np_data = (array)$station->nowplaying_data;

		$np = array(
			'id' => $station->id,
			'category' => $station->category,
			'type' => $station->type,
			'code' => $station->short_name,
			'name' => $station->name,
			'acronym' => $station->acronym,
			'genre' => $station->genre,
			'web' => $station->web_url,
			'player_url' => \DF\Url::route(array('module' => 'default', 'action' => 'tunein', 'id' => $station->id)),
			'logo' => \DF\Url::content($station->image_url, TRUE),
		);

		if ($station->requests_enabled)
			$np['request_url'] = \DF\Url::route(array('module' => 'default', 'controller' => 'station', 'action' => 'request', 'id' => $station->id));

		$np['streams'] = array();
		$np['streams'][] = array('name' => 'Primary', 'url' => $station->stream_url);

		$stream_secondary = $station->stream_alternate;
		if ($stream_secondary)
		{
			$streams = explode("\n", trim($stream_secondary));
			foreach($streams as $stream)
			{
				$stream_parts = explode("|", $stream);
				$np['streams'][] = array(
					'name' => $stream_parts[0],
					'url' => $stream_parts[1],
				);
			}
		}

		if ($station->type)
		{
			$url = $station->nowplaying_url;

			switch($station->type)
    		{
    			case "centovacast":
					$return_raw = self::requestExternalUrl($url);

					if ($return_raw)
					{
						$return = @json_decode($return_raw, TRUE);

						list($artist, $track) = explode(' - ', $return['SERVERTITLE'], 2);

						$np['listeners'] = (int)$return['UNIQUELISTENERS'];
						$np['artist'] = $artist;
						$np['title'] = $track;
						$np['text'] = $return['SERVERTITLE'];
					}
    			break;

    			case "icecast":
    				$return_raw = self::requestExternalUrl($url);

    				if (!$return_raw)
    				{
    					$np['text'] = 'Stream Offline';
    					$np['is_live'] = 'false';
    				}
    				else if (substr($return_raw, 0, 1) == '{')
    				{
    					$return = json_decode($return_raw, true);

    					$np['listeners'] = (int)$return['listeners'];
    					$np['artist'] = $return['now_playing']['artist'];
    					$np['title'] = $return['now_playing']['song'];
    					$np['text'] = $return['title'];
    					$np['is_live'] = ($return['mount'] != '/autodj');
    				}
    				else
    				{
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
						$temp_array = array_slice($temp_array, -10);

						list($artist, $track) = explode(" - ",$temp_array[9], 2);

						$np['listeners'] = $temp_array[5];
						$np['artist'] = $artist;
						$np['title'] = $track;
						$np['text'] = $temp_array[9];
						$np['is_live'] = 'false';
    				}
    			break;

    			case "shoutcast2":
    				$return_raw = self::requestExternalUrl($url);

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
    					$np['listeners'] = (int)$song_data['UNIQUELISTENERS'];
    					$np['is_live'] = 'false'; // ($song_data['NEXTTITLE'] != '') ? 'false' : 'true';
    				}
    				else
    				{
    					$np['text'] = 'Stream Offline';
    					$np['is_live'] = 'false';
    				}
    			break;

    			case "shoutcast1":
    				$return_raw = self::requestExternalUrl($url);

					if ($return_raw)
					{
	    				preg_match("/<body.*>(.*)<\/body>/smU", $return_raw, $return);
	    				$parts = explode(",", $return[1], 7);

	    				list($artist, $title) = explode(" - ", $parts[6], 2);

	    				$np['listeners'] = (int)$parts[0];
	    				$np['title'] = $title;
	    				$np['artist'] = $artist;
	    				$np['text'] = $parts[6];
	    			}
	    			else
	    			{
	    				$np['text'] = 'Stream Offline';
    					$np['is_live'] = 'false';
	    			}
    			break;

    			case "stream":
    			case "video":
    			case "swf":
    			case "iframe":
    			case "link":
    				if (stristr($url, 'livestream') !== FALSE)
    				{
    					$xml = self::requestExternalUrl($url, 30);

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
    				else if (stristr($url, 'twitch.tv') !== FALSE)
    				{
    					$return_raw = self::requestExternalUrl($url);

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
    				else if (stristr($url, 'justin.tv') !== FALSE)
    				{
    					$return_raw = self::requestExternalUrl($url);

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
    				else if (stristr($url, 'bronytv') !== FALSE)
    				{
    					$return_raw = self::requestExternalUrl($url);

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

    					/*
    					$ustream_api = 'http://api.ustream.tv/json/stream/all/search/title:like:btvstream?key=1AF9EEB115A063B2E7B70009C1BC52AF';
    					$ustream_results = self::requestExternalUrl($ustream_api);

    					if ($ustream_results)
    					{
    						$ustream = json_decode($ustream_results, TRUE);
    						$listeners = (int)$ustream['results'][0]['currentNumberOfViewers'];
    					}
    					else
    					{
    						$listeners = 0;
    					}

    					// Regular playback list
    					$return_raw = self::requestExternalUrl($url);

	    				if ($return_raw)
	    				{
	    					$parts = explode("-", str_replace('|', '-', $return_raw), 2);
	    					$parts = array_map(function($x) { return trim($x); }, (array)$parts);

	    					if (substr($return_raw, 0, 1) == '<' || $parts[0] == "Stream is offline")
	    					{
	    						$np['text'] = 'Stream Offline';
	    						$np['is_live'] = 'false';
	    					}
	    					else
	    					{
	    						$np['artist'] = $parts[0];
	    						$np['title'] = $parts[1];
	    						$np['text'] = implode(' - ', $parts);
	    						$np['is_live'] = 'true';
	    						$np['listeners'] = $listeners;
	    					}
						}
						else
						{
							$np['artist'] = 'Offline';
							$np['title'] = 'Offline';
							$np['text'] = 'Stream is Offline';
							$np['is_live'] = 'false';
						}
						*/
    				}

    				if (!$np['text'])
    					$np['text'] = 'Click to Launch Player';
    			break;

    			default:
    				$np['text'] = 'No stream details currently available.';
    			break;
    		}
		}
		else
		{
			$np['text'] = 'No stream details currently available.';
		}

		// Pull from current NP data if song details haven't changed.
		if (strcmp($np['text'], $current_np_data['text']) == 0)
		{
			$np['image'] = $current_np_data['image'];
			$np['song_id'] = $current_np_data['song_id'];
			$np['song_history'] = $current_np_data['song_history'];
		}
		else if (empty($np['text']))
		{
			$np['image'] = $np['logo'];
			$np['song_id'] = NULL;
			$np['song_history'] = $station->getRecentHistory();
		}
		else
		{
			// Send e-mail on the first instance of offline status detected.
			if ($np['text'] == 'Stream Offline')
				self::notifyStation($station, 'offline');

			// Fetch new NP image in the event of a changed song.
			$np['image'] = $np['logo'];
			$station->nowplaying_image = $np['image'];

			// Register a new item in song history.
			$np['song_history'] = $station->getRecentHistory();

			$song_obj = Song::getOrCreate($np);
			$song_obj->playedOnStation($station);
			$np['song_id'] = $song_obj->id;
		}

		// Get currently active event (cached query)
		$np['event'] = Schedule::getCurrentEvent($station->id);
		$np['event_upcoming'] = Schedule::getUpcomingEvent($station->id);

		$station->nowplaying_data = $np;

		$station->nowplaying_artist = $np['artist'];
		$station->nowplaying_title = $np['title'];
		$station->nowplaying_text = $np['text'];
		$station->nowplaying_listeners = (int)$np['listeners'];

		return $np;
	}

	public static function requestExternalUrl($url, $cache_time = 0)
	{
		$cache_name = 'nowplaying_url_'.substr(md5($url), 0, 10);
		if ($cache_time > 0)
		{
			$return_raw = \DF\Cache::load($cache_name);
			if ($return_raw)
				return $return_raw;
		}

		$curl_start = time();

		// Start cURL request.
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);  
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); 
		curl_setopt($curl, CURLOPT_TIMEOUT, 10); 
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.2) Gecko/20070219 Firefox/2.0.0.2');  

		$return_raw = curl_exec($curl);
		// End cURL request.

		$curl_end = time();
		$curl_time = $curl_end - $curl_start;

		echo "\nCurl processed in ".$curl_time." second(s).";

		$error = curl_error($curl);
		if ($error)
			echo "\nCurl Error:".$error;

		if ($cache_time > 0)
		{
			\DF\Cache::save($return_raw, $cache_name, array(), $cache_time);
		}
		
		return $return_raw;
	}

	public static function getNowPlayingImage($np, $station)
    {
    	return \DF\Url::content($station['image_url']);
    }

    public static function notifyStation($station, $template)
    {
    	if (!$station['admin_monitor_station'])
    		return false;

    	$em = \Zend_Registry::get('em');

		$managers_raw = $em->createQuery('SELECT sm.email FROM Entity\StationManager sm WHERE sm.station_id = :station_id')
			->setParameter('station_id', $station['id'])
			->getArrayResult();

		$managers = array();
		foreach($managers_raw as $manager)
			$managers[] = $manager['email'];

		\DF\Messenger::send(array(
            'to'        => $managers,
            'subject'   => 'Station Has Gone Offline',
            'template'  => $template,
            'vars'      => array(
                'station' => $station,
            ),
        ));

        return true;
    }
}