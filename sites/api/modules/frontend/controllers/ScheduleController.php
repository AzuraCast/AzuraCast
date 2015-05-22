<?php
namespace Modules\Frontend\Controllers;

use \Entity\Station;
use \Entity\Schedule;

class ScheduleController extends BaseController
{
    public function indexAction()
    {
        // Get calendar name.
        $short_names = Station::getShortNameLookup();

        $station_shortcode = $this->getParam('station', 'all');
        if ($station_shortcode != "all")
        {
            $station = $short_names[$station_shortcode];
            $calendar_name = $station['name'];
        }
        else
        {
            $calendar_name = 'Ponyville Live!';
        }

        // Get timestamp boundaries.
        if ($this->hasParam('month'))
        {
            $show = $this->getParam('month');
            $calendar = new \DF\Calendar($show);
            $timestamps = $calendar->getTimestamps();
            
            $start_timestamp = $timestamps['start'];
            $end_timestamp = $timestamps['end'];

            $cache_name = 'month_'.$show;
            $calendar_name .= ' - '.date('F Y', $timestamps['mid']);
        }
        elseif ($this->hasParam('start'))
        {
            $start_timestamp = (int)$this->getParam('start');
            $end_timestamp = (int)$this->getParam('end');

            $cache_name = 'range_'.$start_timestamp.'_'.$end_timestamp;
            $calendar_name .= ' - '.date('F j, Y', $start_timestamp).' to '.date('F j, Y', $end_timestamp);
        }
        else
        {
            $start_timestamp = time();
            $end_timestamp = time()+(86400 * 30);

            $cache_name = 'upcoming';
            $calendar_name .= ' - Upcoming';
        }

        // Load from cache or regenerate.
        $cache_name = 'api_schedule_'.$station_shortcode.'_'.$cache_name;
        $events = \DF\Cache::get($cache_name);

        if (!$events)
        {
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
                $events[] = Schedule::api($event);

            \DF\Cache::save($events, $cache_name, array(), 300);
        }

        $format = strtolower($this->getParam('format', 'json'));
        switch($format)
        {
            case "ics":
            case "ical":
                return $this->_printCalendar($events, $calendar_name, $cache_name);
            break;

            case "json":
            default:
                return $this->returnSuccess($events);
            break;
        }
    }

    protected function _printCalendar($events, $name = 'Ponyville Live! - Events', $filename = 'calendar')
    {
        $filename = str_replace('api_', '', $filename);

        $this->response->setContentType('text/calendar', 'utf-8');
        $this->response->setHeader('Content-Disposition', 'attachment; filename='.$filename.'.ics');

        $cal = array();
        $cal[] = 'BEGIN:VCALENDAR';
        $cal[] = 'VERSION:2.0';
        $cal[] = 'PRODID:-//pvlcalendar//NONSGML v1.0//EN';
        $cal[] = 'CALSCALE:GREGORIAN';
        $cal[] = 'TZID:Europe/London';
        $cal[] = 'X-WR-CALNAME:'.$this->_calString($name);
        $cal[] = 'METHOD:PUBLISH';

        foreach($events as $row)
        {
            if (empty($row['station']))
            {
                $row['station'] = array(
                    'name' => 'Ponyville Live!',
                    'web_url' => 'http://ponyvillelive.com/',
                );
            }

            $cal[] = 'BEGIN:VEVENT';
            $cal[] = 'DTSTART:'.$this->_calDate($row['start_time'], $row['is_all_day']);
            $cal[] = 'DTEND:'.$this->_calDate($row['end_time'], $row['is_all_day']);
            $cal[] = 'UID:'.$row['guid'];
            $cal[] = 'DTSTAMP:'.$this->_calDate(time());
            $cal[] = 'LOCATION:'.$this->_calString($row['station']['name']);
            $cal[] = 'URL;VALUE=URI:'.$this->_calString($row['station']['web_url']);
            $cal[] = 'SUMMARY:'.$this->_calString($row['title']);
            $cal[] = 'DESCRIPTION:'.$this->_calString($row['title'].' on '.$row['station']['name']);
            $cal[] = 'END:VEVENT';
        }

        $cal[] = 'END:VCALENDAR';
        return $this->response->setContent(implode(PHP_EOL, $cal));
    }

    protected function _calDate($timestamp, $date_only=false)
    {
        if ($date_only)
            return gmdate('Ymd', $timestamp);
        else
            return gmdate('Ymd\THis\Z', $timestamp);
    }

    protected function _calString($string)
    {
        return preg_replace('/([\,;])/','\\\$1', $string);
    }

    public function conventionsAction()
    {
        return $this->returnError('This function is deprecated for api/conventions/list');
    }
}