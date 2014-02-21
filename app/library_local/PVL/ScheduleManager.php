<?php
namespace PVL;

use \Entity\Station;
use \Entity\Schedule;
use \Entity\Settings;

class ScheduleManager
{
	public static function run($force_run = false)
	{
		$em = \Zend_Registry::get('em');
		$config = \Zend_Registry::get('config');

		$last_run = Settings::getSetting('schedule_manager_last_run', 0);
		if ($last_run > (time() - 900) && !$force_run)
			return;

		$schedule_items = array();
		$schedule_records = array();
		$schedule_stations = array();

		// PVL news
		$schedule_items[] = array(
			'name'		=> 'PVL Global Events',
			'url'		=> 'https://www.google.com/calendar/feeds/lj3d00magjlucuk902rarrhhrg%40group.calendar.google.com/public/full',
			'type'		=> 'convention',
			'station_id' => 0,
			'image_url'	=> \DF\Url::content('pvl_square.png'),
		);
		$schedule_stations[0] = NULL;

		$stations = $em->createQuery('SELECT s FROM Entity\Station s WHERE s.is_active = 1')
			->execute();

		foreach($stations as $station)
		{
			if ($station->gcal_url)
			{
				$schedule_stations[$station->id] = $station;

				$schedule_items[] = array(
					'name'		=> $station->name,
					'url'		=> $station->gcal_url,
					'type'		=> 'station',
					'station_id' => $station->id,
					'image_url'	=> \DF\Url::content($station->image_url),
				);
			}
		}

		\PVL\Debug::startTimer('Get Calendar Records');

		$time_check_start = time();

		foreach($schedule_items as $item)
		{
			$start_time = date(\DateTime::RFC3339, strtotime('-1 week'));
	        $end_time = date(\DateTime::RFC3339, strtotime('+1 year'));
	        $http_params = array(
	            'alt'           => 'json',
	            'recurrence-expansion-start' => $start_time,
	            'recurrence-expansion-end' => $end_time,
	            'start-min'     => $start_time,
	            'start-max'     => $end_time,
	            'max-results'   => 200,
	            'singleevents'  => 'true',
	            'orderby'       => 'starttime',
	            'sortorder'     => 'ascending',
	            'ctz'           => date_default_timezone_get(),
	        );

	        $base_url = $item['url'];
	        $http_url = str_replace('/basic', '/full', $base_url).'?'.http_build_query($http_params);
	        $calendar_raw = self::requestExternalUrl($http_url, $item['name']);

	        if ($calendar_raw)
	        {
	            $calendar_array = json_decode($calendar_raw, true);
	            $events = (array)$calendar_array['feed']['entry'];

	            // \PVL\Debug::print_r($calendar_array);

	            $all_events = array();

	            foreach($events as $event_orig)
	            {
	                $title = trim($event_orig['title']['$t']);
	                $body = trim($event_orig['content']['$t']);
	                $location = trim($event_orig['gd$where'][0]['valueString']);

	                $web_url = trim($event_orig['link'][0]['href']);

	                $is_all_day = false;

	                $start_time = trim($event_orig['gd$when'][0]['startTime']);
	                if (strlen($start_time) == 10)
	                {
	                	$is_all_day = true;
	                    $start_time = strtotime($start_time.' 00:00:00');
	                }
	                else
	                {
	                    $start_time = strtotime($start_time);
	                }

	                $end_time = trim($event_orig['gd$when'][0]['endTime']);
	                if (strlen($end_time) == 10)
	                {
	                	$is_all_day = true;
	                    $end_time = strtotime($end_time.' 00:00:00')-1;
	                }
	                else
	                {
	                    $end_time = strtotime($end_time);
	                }

	                // Detect URLs.
	                if ($body)
	                {
		                preg_match('@((https?://)?([-\w]+\.[-\w\.]+)+\w(:\d+)?(/([-\w/_\.]*(\?\S+)?)?)*)@',$body, $urls);

		                if (count($urls) > 0)
		                	$web_url = $urls[0];
		            }

	                $schedule_record = array(
                		'type'		=> $item['type'],
	               		'start_time' => $start_time,
	               		'end_time'	=> $end_time,
	               		'is_all_day' => $is_all_day,
	               		'title'		=> $title,
	               		'location'	=> $location,
	               		'body'		=> $body,
	               		'image_url'	=> $item['image_url'],
	               		'web_url'	=> $web_url,
	                );

	                $guid = md5(json_encode($schedule_record));
	                $schedule_record['guid'] = $guid;

	                $schedule_records[$item['station_id']][$guid] = $schedule_record;
	            }
	        }
	    }

	    \PVL\Debug::endTimer('Get Calendar Records');

	    // Add/Remove all differential records.
	    \PVL\Debug::startTimer('Sync DB Records');

	    /*
	    $em->createQuery('DELETE FROM Entity\Schedule s WHERE s.type = :type AND s.id NOT IN (:station_ids)')
	    	->setParameter('type', 'station')
	    	->setParameter('station_ids', array_keys($schedule_records))
	    	->execute();
	    */

	    foreach($schedule_records as $station_id => $station_records)
	    {
	    	$station = $schedule_stations[$station_id];

	    	if ($station_id == 0)
	    	{
	    		$existing_guids_raw = $em->createQuery('SELECT s.guid FROM Entity\Schedule s WHERE s.station_id IS NULL')
	    			->getArrayResult();
	    	}
	    	else
	    	{
	    		$existing_guids_raw = $em->createQuery('SELECT s.guid FROM Entity\Schedule s WHERE s.station_id = :sid')
	    			->setParameter('sid', $station_id)
	    			->getArrayResult();
	    	}

	    	$existing_guids = array();
	    	foreach($existing_guids_raw as $i)
	    		$existing_guids[] = $i['guid'];

	    	$new_guids = array_keys($station_records);



	    	$guids_to_delete = array_diff($existing_guids, $new_guids);

	    	if ($guids_to_delete)
	    	{
	    		$em->createQuery('DELETE FROM Entity\Schedule s WHERE s.guid IN (:guids)')
	    			->setParameter('guids', $guids_to_delete)
	    			->execute();
	    	}

	    	$guids_to_add = array_diff($new_guids, $existing_guids);

	    	if ($guids_to_add)
	    	{
	    		foreach($guids_to_add as $guid)
	    		{
	    			$schedule_record = $station_records[$guid];

	    			$record = new Schedule;
		           	$record->station = $station;
		           	$record->fromArray($schedule_record);
		           	$em->persist($record);
	    		}
	    	}
	    }

	    $em->flush();

	    \PVL\Debug::endTimer('Sync DB Records');

		Settings::setSetting('schedule_manager_last_run', time());
	}

	public static function requestExternalUrl($url, $name = 'Calendar')
	{
		\PVL\Debug::startTimer('Request URL '.$name);

		// Start cURL request.
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);  
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); 
		curl_setopt($curl, CURLOPT_TIMEOUT, 10); 
		curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.2) Gecko/20070219 Firefox/2.0.0.2');  

		$return_raw = curl_exec($curl);
		// End cURL request.

		\PVL\Debug::endTimer('Request URL '.$name);

		$error = curl_error($curl);
		if ($error)
			\PVL\Debug::log('Curl Error:'.$error);
		
		return $return_raw;
	}
	
	public static function formatName($name)
	{
		list($title, $city) = explode('-', $name);

		$special_codes = array(
            '@' => array('icon' => 'icon-star', 'text' => 'PVL Special Event'),
            '*' => array('icon' => 'icon-group', 'text' => 'PVL Convention Coverage'),
            '+' => array('icon' => 'icon-group', 'text' => 'PVL Convention Attendance'),
        );

		$return = array(
			'title' => trim($title),
			'city' => trim($city),
			'title_full' => trim($title),
			'category' => '',
			'icon' => '',
		);

        foreach($special_codes as $symbol => $full)
        {
            if (substr($return['title'], 0, strlen($symbol)) == $symbol)
            {
            	$return['category'] = $full['text'];
            	$return['icon'] = $full['icon'];

            	$title_addon = '<br><small><i class="'.$full['icon'].'"></i> '.$full['text'].'</small>';

            	$return['title'] = trim(substr($return['title'], strlen($symbol)));
            	$return['title_full'] = $return['title'].$title_addon;
            	break;
            }
        }

        return $return;
	}

}