<?php
use \Entity\Event;
use \Entity\EventType;

class EventsController extends \DF\Controller\Action
{
	public function indexAction()
    {
        $show = ($this->_hasParam('month')) ? $this->_getParam('month') : date('Ym');
        $calendar = new \DF\Calendar($show);

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
            $event['start_timestamp'] = $event['start_time'];
            $event['end_timestamp'] = $event['end_time'];

            $special_info = \PVL\ScheduleManager::formatName($event['title']);
            $event['title'] = $special_info['title'];
            $event['city'] = $special_info['city'];
            $event['title_full'] = $special_info['title_full'];

            $events[] = $event;
        }

        $this->view->all_events = $events;
        $this->view->calendar = $calendar->fetch($events);
        $this->view->show_map = ($show == date('Ym'));
    }

    public function scheduleAction()
    {
        $all_stations = \Entity\Station::fetchArray();
        $this->view->all_stations = $all_stations;

        $stations = array();
        foreach($all_stations as $station_info)
            $stations[$station_info['short_name']] = $station_info;

        $show = ($this->_hasParam('month')) ? $this->_getParam('month') : date('Ym');
        $calendar = new \DF\Calendar($show);

        $timestamps = $calendar->getTimestamps();

        $station_shortcode = $this->_getParam('station', 'all');
        $this->view->station = $station_shortcode;

        if ($station_shortcode != "all")
        {
            $station = $stations[$station_shortcode];

            $events_raw = $this->em->createQuery('SELECT s, st FROM Entity\Schedule s LEFT JOIN s.station st WHERE (s.station_id = :sid) AND (s.start_time <= :end AND s.end_time >= :start) ORDER BY s.start_time ASC')
                ->setParameter('sid', $station['id'])
                ->setParameter('start', $timestamps['start'])
                ->setParameter('end', $timestamps['end'])
                ->getArrayResult();
        }
        else
        {
            $events_raw = $this->em->createQuery('SELECT s, st FROM Entity\Schedule s LEFT JOIN s.station st WHERE (s.start_time <= :end AND s.end_time >= :start) ORDER BY s.start_time ASC')
                ->setParameter('start', $timestamps['start'])
                ->setParameter('end', $timestamps['end'])
                ->getArrayResult();
        }

        $events = array();
        foreach((array)$events_raw as $event)
        {
            $event['start_timestamp'] = $event['start_time'];
            $event['end_timestamp'] = $event['end_time'];

            $events[] = $event;
        }

        $this->view->calendar = $calendar->fetch($events);
    }
}