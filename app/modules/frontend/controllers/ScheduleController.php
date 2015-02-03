<?php
namespace Modules\Frontend\Controllers;

use \Entity\Event;
use \Entity\EventType;
use \Entity\Station;

class ScheduleController extends BaseController
{
    public function indexAction()
    {
        $all_stations = Station::fetchArray();
        $this->view->all_stations = $all_stations;

        $stations = array();
        foreach($all_stations as $station_info)
            $stations[$station_info['short_name']] = $station_info;

        $show = ($this->hasParam('month')) ? $this->getParam('month') : date('Ym');
        $calendar = new \DF\Calendar($show);

        $timestamps = $calendar->getTimestamps();

        $station_shortcode = $this->getParam('station', 'all');
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