<?php
namespace Modules\Frontend\Controllers;

use Entity\Station;
use Entity\Podcast;
use Entity\NetworkNews;
use Entity\Settings;
use Entity\Schedule;
use Entity\Rotator;
use Entity\Convention;
use Entity\VideoChannel;

class IndexController extends BaseController
{
    public function indexAction()
    {
        $this->forceInsecure();

        // Pull podcasts.
        $podcasts = Podcast::fetchLatest();
        $this->view->podcasts = $podcasts;

        // Pull large photos and news for rotator.
        $network_news = NetworkNews::fetchFeatured();
        $this->view->network_news = $network_news;

        // Pull stations.
        $this->_initStations();

        // Pull conventions.
        $conventions = Convention::getAllConventions();
        $this->view->conventions_upcoming = $conventions['upcoming'];
        $this->view->conventions_archived = $conventions['archived'];

        // Pull rotators.
        $rotators = Rotator::fetch();
        $this->view->rotators = $rotators;

        // Special event flagging and special formatting.
        $special_event = \PVL\Utilities::showSpecialEventsMode();
        $this->view->special_event = $special_event;

        if ($special_event)
        {
            $autoplay_station = Settings::getSetting('special_event_station_id', 0);

            if ($autoplay_station != 0)
            {
                $this->view->station_id = $autoplay_station;
                $this->view->autoplay = true;
            }

            $this->view->special_event_embed = trim(Settings::getSetting('special_event_embed_code'));
            $this->view->special_chat_embed = trim(Settings::getSetting('special_event_chat_code'));
        }

        $this->view->autoplay = (bool)$this->getParam('autoplay', true);
    }

    public function tuneinAction()
    {
        $this->forceInsecure();

        // Disable session creation.
        \DF\Session::disable();

        // Switch to maintenance theme.
        $this->view->setLayout('maintenance');

        $this->view->embed_mode = ($this->getParam('embed', 'false') == 'true');
        $this->view->skin = ($this->getParam('skin'));

        $autoplay = $this->getParam('autoplay');

        if ($this->hasParam('autoplay'))
            $autoplay = ($autoplay === 'true' || $autoplay === '1');
        else
            $autoplay = false;

        $this->view->autoplay = $autoplay;
        $this->view->standalone = true;

        $this->_initStations();
    }

    public function chatAction()
    {
        $this->forceInsecure();

        $this->_initStations();

        // Pull podcasts.
        $podcasts = Podcast::fetchLatest();
        $this->view->podcasts = $podcasts;
    }

    public function appAction()
    {}

    public function aboutAction()
    {}

    public function contactAction()
    {
        $all_categories = Station::getStationsInCategories();

        $audio_stations = \DF\Utilities::columns($all_categories['audio']['stations'], 2);
        $video_stations = $all_categories['video']['stations'];

        $this->view->station_columns = array(
            array(
                '<i class="icon '.$all_categories['audio']['icon'].'"></i> '.$all_categories['audio']['name'],
                $audio_stations[0],
            ),
            array(
                '&nbsp;',
                $audio_stations[1],
            ),
            array(
                '<i class="icon '.$all_categories['video']['icon'].'"></i> '.$all_categories['video']['name'],
                $video_stations,
            ),
        );

        $active_podcasts = array_filter(Podcast::fetchArray('name'), function($row) { return $row['is_approved']; });
        $this->view->podcasts = \DF\Utilities::columns($active_podcasts, 3);

        $this->view->podcast_social_types = Podcast::getSocialTypes();
    }

    public function donateAction()
    {}

    public function mobileAction()
    {
        return $this->response->redirect('http://m.pvlive.me');
    }

    public function scheduleAction()
    {
        // Pull stations.
        $this->_initStations();
    }

    public function upcomingAction()
    {
        $this->_initStations();

        $id = $this->view->station_id;

        $station = Station::find($id);
        if (!($station instanceof Station))
            throw new \DF\Exception\DisplayOnly('Station not found!');

        $this->view->station = $station;

        // Filter events from master events list.
        $events_by_day = $this->view->events_by_day;
        $station_events = array();

        foreach($events_by_day as $day_date => $day_info)
        {
            $day_events = array();

            foreach($day_info['events'] as $event)
            {
                if ($event['station_id'] == $id)
                    $day_events[] = $event;
            }

            $day_info['events'] = $day_events;
            if (!empty($day_info['events']))
                $station_events[$day_date] = $day_info;
        }

        $this->view->station_events = $station_events;
    }

    /**
     * Protected Functions
     */

    protected $stations;
    protected $categories;

    protected function _initStations()
    {
        $this->view->station_id = $station_id = $this->getParam('id', NULL);
        $this->view->volume = ($this->hasParam('volume')) ? (int)$this->getParam('volume') : 30;

        $this->categories = \Entity\Station::getCategories();

        $stations_raw = Station::fetchArray();

        // Limit to a single station if requested.
        if ($station_id && $this->getParam('showonlystation', false) == 'true')
        {
            foreach($stations_raw as $station)
            {
                if ($station['id'] == $station_id)
                {
                    $stations_raw = array($station);
                    break;
                }
            }
        }

        $this->stations = array();
        foreach($stations_raw as $station)
        {
            // Build multi-stream directory.
            $streams = array();
            $current_stream_id = NULL;

            foreach((array)$station['streams'] as $stream)
            {
                if (!$stream['hidden_from_player'] && $stream['is_active'])
                {
                    if ($stream['is_default'])
                    {
                        $station['default_stream_id'] = $stream['id'];
                        $current_stream_id = $stream['id'];
                    }

                    $streams[$stream['id']] = $stream;
                }
            }

            // Pull from user preferences to potentially override defaults.
            $default_streams = (array)\PVL\Customization::get('stream_defaults');

            if (isset($default_streams[$station['id']]))
            {
                $stream_id = (int)$default_streams[$station['id']];
                if (isset($streams[$stream_id]))
                    $current_stream_id = $stream_id;
            }

            $station['current_stream_id'] = $current_stream_id;
            $station['streams'] = $streams;

            // Only show stations with at least one usable stream.
            if (count($streams) > 0)
                $this->stations[$station['id']] = $station;
        }

        foreach($this->stations as $station)
        {
            if (isset($this->categories[$station['category']]))
                $this->categories[$station['category']]['stations'][] = $station;
        }

        $this->view->stations = $this->stations;
        $this->view->categories = $this->categories;

        /**
         * Compose events
         */

        $events_raw = $this->em->createQuery('SELECT s, st FROM Entity\Schedule s JOIN s.station st WHERE (s.end_time >= :current AND s.start_time <= :future) ORDER BY s.start_time ASC')
            ->setParameter('current', time())
            ->setParameter('future', strtotime('+1 week'))
            ->getArrayResult();

        $all_events = array();
        $events_by_day = array();

        for($i = 0; $i < 6; $i++)
        {
            $day_timestamp = mktime(0, 0, 1, date('n'), (int)date('j') + $i);
            $day_date = date('Y-m-d', $day_timestamp);

            $is_today = ($day_date == date('Y-m-d'));

            $events_by_day[$day_date] = array(
                'day_name'      => ($is_today) ? 'Today' : date('l', $day_timestamp),
                'timestamp'     => $day_timestamp,
                'is_today'      => $is_today,
                'events'        => array(),
            );
        }

        foreach($events_raw as $event)
        {
            $event['image_url'] = \DF\Url::content(Schedule::getRowImageUrl($event));
            $event['status'] = ($event['start_time'] <= time()) ? 'now' : 'upcoming';
            $event['range'] = Schedule::getRangeText($event['start_time'], $event['end_time'], $event['is_all_day']);

            if ($event['station_id'])
            {
                $sid = $event['station_id'];

                if (isset($this->stations[$sid]))
                {
                    $event['station'] = $this->stations[$sid];
                    $this->stations[$sid]['events'][] = $event;

                    if ($event['status'] == "now")
                        $this->stations[$sid]['now'] = $event;
                }
            }

            $all_events[] = $event;

            $event_date = date('Y-m-d', $event['start_time']);
            if (isset($events_by_day[$event_date]))
                $events_by_day[$event_date]['events'][] = $event;
        }

        $this->view->events_by_day = $events_by_day;
        $this->view->all_events = $all_events;
    }

    public function nowplayingAction()
    {
        $this->redirect(\DF\Url::content('api/nowplaying.json'));
        return;
    }
}