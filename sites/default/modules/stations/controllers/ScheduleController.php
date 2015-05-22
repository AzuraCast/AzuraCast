<?php
namespace Modules\Stations\Controllers;

use Entity\Station;
use Entity\Schedule;

class ScheduleController extends BaseController
{
    public function indexAction()
    {
        $events_raw = $this->em->createQuery('SELECT s FROM \Entity\Schedule s WHERE (s.end_time >= :current AND s.start_time <= :future) AND s.station_id = :station_id ORDER BY s.start_time ASC')
            ->setParameter('station_id', $this->station->id)
            ->setParameter('current', time())
            ->setParameter('future', strtotime('+2 months'))
            ->getArrayResult();

        $events_by_day = array();

        foreach($events_raw as $event)
        {
            $event['status'] = ($event['start_time'] <= time()) ? 'now' : 'upcoming';
            $event['range'] = Schedule::getRangeText($event['start_time'], $event['end_time'], $event['is_all_day']);

            $event_date = date('Y-m-d', $event['start_time']);
            if (!isset($events_by_day[$event_date]))
            {
                $events_by_day[$event_date] = array(
                    'name' => date('l, F j, Y', $event['start_time']),
                    'events' => array(),
                );
            }

            $events_by_day[$event_date]['events'][] = $event;
        }

        $this->view->events_by_day = $events_by_day;
    }

    public function promoteAction()
    {
        $event_guid = $this->getParam('event');

        $event = Schedule::getRepository()->findOneBy(array('guid' => $event_guid, 'station_id' => $this->station->id));

        if (!($event instanceof Schedule))
            throw new \DF\Exception\DisplayOnly('Event not found!');

        $banner_url = $this->station->banner_url;
        if (empty($banner_url))
            throw new \DF\Exception\DisplayOnly('You have not supplied a banner for your station yet! Please visit the "Edit Profile" page to supply a banner image.');

        $event->is_promoted = !($event->is_promoted);
        $event->save();

        $this->alert('<b>Event promotion toggled!</b>', 'green');
        return $this->redirectFromHere(array('action' => 'index', 'event' => NULL));
    }
}