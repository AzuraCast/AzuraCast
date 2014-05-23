<?php
namespace PVL;

use \Entity\Statistic;
use \Entity\Schedule;
use \Entity\Station;
use \Entity\Song;
use \Entity\Settings;

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
		$nowplaying_feed = json_encode($nowplaying, JSON_UNESCAPED_SLASHES);
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

    		\PVL\Debug::log('Station "'.$station->name.'" processing starting.');
    		
    		$start_time = time();

    		$np = self::processStation($station);

    		$end_time = time();
    		$time_taken = $end_time - $start_time;

    		\PVL\Debug::log('Station "'.$station->name.'" processed in '.$time_taken.' seconds.');
    		\PVL\Debug::log('---');

    		$name = $station->short_name;
    		$nowplaying[$name] = $np;
    		
    		$em->persist($station);
    		$em->flush();
    		$em->clear();
    	}

    	$overall_end = time();
    	$overall_total = $overall_end - $overall_start;
    	
    	\PVL\Debug::log("Now Playing finished in ".$overall_total." seconds.");

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

			$custom_class = Station::getStationClassName($station->name);
			$custom_adapter = '\\PVL\\NowPlayingAdapter\\'.$custom_class;

			if (class_exists($custom_adapter))
				$np_adapter = new $custom_adapter($station);
			elseif ($station->type == "centovacast")
				$np_adapter = new \PVL\NowPlayingAdapter\CentovaCast($station);
			elseif ($station->type == "icecast")
				$np_adapter = new \PVL\NowPlayingAdapter\IceCast($station);
			elseif ($station->type == "shoutcast2")
				$np_adapter = new \PVL\NowPlayingAdapter\ShoutCast2($station);
			elseif ($station->type == "shoutcast1")
				$np_adapter = new \PVL\NowPlayingAdapter\ShoutCast1($station);
			elseif ($station->type == "stream")
				$np_adapter = new \PVL\NowPlayingAdapter\Stream($station);

			\PVL\Debug::log('Adapter Class: '.get_class($np_adapter));

			$np = $np_adapter->process($np);
		}
		else
		{
			$np['text'] = 'Error Processing Stream';
			$np['is_live'] = false;
			$np['status'] = 'offline';
		}

		// Pull from current NP data if song details haven't changed.
		if (strcmp($np['text'], $current_np_data['text']) == 0)
		{
			// $np['image'] = $current_np_data['image'];
			$np['song_id'] = $current_np_data['song_id'];
			$np['song_history'] = $current_np_data['song_history'];
		}
		else if (empty($np['text']))
		{
			// $np['image'] = $np['logo'];
			$np['song_id'] = NULL;
			$np['song_history'] = $station->getRecentHistory();
		}
		else
		{
			// Send e-mail on the first instance of offline status detected.
			if ($np['text'] == 'Stream Offline')
				self::notifyStation($station, 'offline');

			// Fetch new NP image in the event of a changed song.
			// $np['image'] = $np['logo'];
			// $station->nowplaying_image = $np['image'];

			// Register a new item in song history.
			$np['song_history'] = $station->getRecentHistory();

			$song_obj = Song::getOrCreate($np);
			$song_obj->playedOnStation($station, $np);
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

	public static function getNowPlayingImage($np, $station)
    {
    	return \DF\Url::content($station['image_url']);
    }

    public static function notifyStation($station, $template)
    {
    	if (true || !$station['admin_monitor_station'])
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