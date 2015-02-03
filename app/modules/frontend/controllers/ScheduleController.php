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

        $station_shortcode = $this->getParam('station', 'all');
        $this->view->station = $station_shortcode;
    }

    public function fetchAction()
    {
        $this->doNotRender();

        $all_stations = Station::fetchArray();
        $stations = array();
        foreach($all_stations as $station_info)
            $stations[$station_info['short_name']] = $station_info;

        $station_shortcode = $this->getParam('station', 'all');

        $timestamps = array(
            'start' => strtotime($this->getParam('start').' 00:00:00'),
            'end' => strtotime($this->getParam('end').' 23:59:59')+1,
        );

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
            $station = NULL;

            $events_raw = $this->em->createQuery('SELECT s, st FROM Entity\Schedule s LEFT JOIN s.station st WHERE (s.start_time <= :end AND s.end_time >= :start) ORDER BY s.start_time ASC')
                ->setParameter('start', $timestamps['start'])
                ->setParameter('end', $timestamps['end'])
                ->getArrayResult();
        }

        $events = array();
        foreach((array)$events_raw as $event)
        {
            if (!$station)
                $event['title'] = $event['station']['name'].":\n".$event['title'];

            $events[] = array(
                'id'        => $event['guid'],
                'title'     => $event['title'],
                'allDay'    => $event['is_all_day'] ? true : false,
                'start'     => date(\DateTime::ISO8601, $event['start_time']),
                'end'       => date(\DateTime::ISO8601, $event['end_time']),
                'url'       => $event['web_url'],
            );
        }

        return $this->response->setJsonContent($events);
    }
}