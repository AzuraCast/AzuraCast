<?php
use \Entity\Station;
use \Entity\Schedule;

class Api_ScheduleController extends \PVL\Controller\Action\Api
{
    public function indexAction()
    {
    	if ($this->_hasParam('month'))
    	{
    		$show = $this->_getParam('month');
        	$calendar = new \DF\Calendar($show);
        	$timestamps = $calendar->getTimestamps();
            
        	$start_timestamp = $timestamps['start'];
        	$end_timestamp = $timestamps['end'];

            $cache_name = 'month_'.$show;
        }
        elseif ($this->_hasParam('start'))
        {
            $start_timestamp = (int)$this->_getParam('start');
        	$end_timestamp = (int)$this->_getParam('end');

            $cache_name = 'range_'.$start_timestamp.'_'.$end_timestamp;
        }
        else
        {
        	$start_timestamp = time();
        	$end_timestamp = time()+(86400 * 30);

            $cache_name = 'upcoming';
        }

        $cache_name = 'api_schedule_'.$cache_name;
        $events = \DF\Cache::get($cache_name);

        if (!$events)
        {
            $station_shortcode = $this->_getParam('station', 'all');
            $short_names = Station::getShortNameLookup();

            if ($station_shortcode != "all")
            {
                $station = $short_names[$station_shortcode];

                $events_raw = $this->em->createQuery('SELECT s FROM Entity\Schedule s WHERE (s.station_id = :sid) AND (s.start_time <= :end AND s.end_time >= :start) ORDER BY s.start_time ASC')
                    ->setParameter('sid', $station['id'])
                    ->setParameter('start', $start_timestamp)
                    ->setParameter('end', $end_timestamp)
                    ->getArrayResult();
            }
            else
            {
                $events_raw = $this->em->createQuery('SELECT s, st FROM Entity\Schedule s LEFT JOIN s.station st WHERE (s.start_time <= :end AND s.end_time >= :start) ORDER BY s.start_time ASC')
                    ->setParameter('start', $start_timestamp)
                    ->setParameter('end', $end_timestamp)
                    ->getArrayResult();
            }

            $events = array();
            foreach((array)$events_raw as $event)
            {
            	if ($station_shortcode == 'all')
            	{
            		$shortcode = Station::getStationShortName($event['station']['name']);
            		$event['station'] = $shortcode;
            	}
            	else
            	{
            		unset($event['station']);
            	}
            	
            	unset($event['is_notified']);

                $events[] = $event;
            }

            \DF\Cache::save($events, $cache_name, array(), 300);
        }

        $this->returnSuccess($events);
    }

    public function conventionsAction()
    {
    	$start_timestamp = strtotime('-1 month');
        $end_timestamp = strtotime('+1 year');

        $events_raw = $this->em->createQuery('SELECT s FROM Entity\Schedule s WHERE s.type = :type AND s.start_time <= :end AND s.end_time >= :start ORDER BY s.start_time ASC')
            ->setParameter('type', 'convention')
            ->setParameter('start', $start_timestamp)
            ->setParameter('end', $end_timestamp)
            ->getArrayResult();

        $events = array();
        foreach((array)$events_raw as $event)
        {
        	$special_info = \PVL\ScheduleManager::formatName($event['title']);
            $event = array_merge($event, $special_info);

            unset($event['station_id'], $event['icon'], $event['is_notified']);

            $events[] = $event;
        }

        $this->returnSuccess($events);
    }
}