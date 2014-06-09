<?php
use \Entity\Station;
use \Entity\Schedule;

class Api_ScheduleController extends \PVL\Controller\Action\Api
{
    public function indexAction()
    {
        if ($this->hasParam('month'))
        {
            $show = $this->getParam('month');
            $calendar = new \DF\Calendar($show);
            $timestamps = $calendar->getTimestamps();
            
            $start_timestamp = $timestamps['start'];
            $end_timestamp = $timestamps['end'];

            $cache_name = 'month_'.$show;
        }
        elseif ($this->hasParam('start'))
        {
            $start_timestamp = (int)$this->getParam('start');
            $end_timestamp = (int)$this->getParam('end');

            $cache_name = 'range_'.$start_timestamp.'_'.$end_timestamp;
        }
        else
        {
            $start_timestamp = time();
            $end_timestamp = time()+(86400 * 30);

            $cache_name = 'upcoming';
        }

        $station_shortcode = $this->getParam('station', 'all');
        $cache_name = 'api_schedule_'.$station_shortcode.'_'.$cache_name;
        $events = \DF\Cache::get($cache_name);

        if (!$events)
        {
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

        $format = strtolower($this->getParam('format', 'json'));
        switch($format)
        {
            case "ics":
            case "ical":
                $this->_printCalendar($events);
            break;

            case "json":
            default:
                $this->returnSuccess($events);
            break;
        }
    }

    protected function _printCalendar($events, $calendar_name = 'calendar')
    {
        $calendar_name = str_replace('api_', '', $calendar_name);

        header('Content-type: text/calendar; charset=utf-8');
        header('Content-Disposition: attachment; filename='.$calendar_name.'.ics');

        $stations = Station::fetchArray();

        $cal = array();
        $cal[] = 'BEGIN:VCALENDAR';
        $cal[] = 'VERSION:2.0';
        $cal[] = 'PRODID:-//pvlcalendar//NONSGML v1.0//EN';
        $cal[] = 'CALSCALE:GREGORIAN';

        foreach($events as $row)
        {
            if ($row['station'])
            {
                $row['station'] = $stations[$row['station']];
            }
            else
            {
                $row['station'] = array(
                    'name' => 'Ponyville Live!',
                    'web_url' => 'http://ponyvillelive.com/',
                );
            }

            $cal[] = 'BEGIN:VEVENT';
            $cal[] = 'DTSTART:'.$this->_calDate($row['start_time']);
            $cal[] = 'DTEND:'.$this->_calDate($row['end_time']);
            $cal[] = 'UID:'.$row['guid'];
            $cal[] = 'DTSTAMP:'.$this->_calDate(time());
            $cal[] = 'LOCATION:'.$this->_calString($row['station']['name']);
            $cal[] = 'URL;VALUE=URI:'.$this->_calString($row['station']['web_url']);
            $cal[] = 'SUMMARY:'.$this->_calString($row['title']);
            $cal[] = 'DESCRIPTION:'.$this->_calString($row['title'].' on '.$row['station']['name']);
            $cal[] = 'END:VEVENT';
        }

        $cal[] = 'END:VCALENDAR';
        echo implode(PHP_EOL, $cal);
    }

    protected function _calDate($timestamp)
    {
        return gmdate('Ymd\THis\Z', $timestamp);
    }

    protected function _calString($string)
    {
        return preg_replace('/([\,;])/','\\\$1', $string);
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