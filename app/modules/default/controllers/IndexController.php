<?php
use \Entity\Station;
use \Entity\Artist;
use \Entity\Podcast;
use \Entity\Event;
use \Entity\NetworkNews;
use \Entity\Settings;
use \Entity\Schedule;
use \Entity\Rotator;
use \Entity\Convention;

class IndexController extends \DF\Controller\Action
{
    public function indexAction()
    {
        // Pull podcasts.
        $podcasts = Podcast::fetchLatest();
        $this->view->podcasts = $podcasts;

        // Pull large photos and news for rotator.
        $network_news = NetworkNews::fetch();
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

        $this->view->autoplay = (bool)$this->_getParam('autoplay', true);

        $render_mode = $this->_getParam('mode', 'default');

        if ($render_mode == 'chat')
            $this->render('index_chat');
        else
            $this->render();
    }

    public function tuneinAction()
    {
        $this->view->layout()->setLayout('maintenance');
        $this->view->embed_mode = ($this->_getParam('embed', 'false') == 'true');
        $this->view->skin = ($this->_getParam('skin'));

        $autoplay = $this->_getParam('autoplay');

        if ($this->_hasParam('autoplay'))
            $autoplay = ($autoplay === 'true' || $autoplay === '1');
        else
            $autoplay = false;

        $this->view->autoplay = $autoplay;
        $this->view->standalone = true;

        $this->_initStations();
    }

    public function chatAction()
    {
        $this->view->layout()->setLayout('maintenance');
        $this->view->skin = $this->_getParam('skin', 'dark');

        $this->_initStations();
    }

    public function appAction()
    {}

    public function scheduleAction()
    {
        // Pull stations.
        $this->_initStations();
    }

    public function upcomingAction()
    {

    }

    /**
     * Protected Functions
     */

    protected $stations;
    protected $categories;

    protected function _initStations()
    {
        $this->view->station_id = $station_id = $this->_getParam('id', NULL);
        $this->view->volume = ($this->hasParam('volume')) ? (int)$this->_getParam('volume') : 30;

        $this->categories = \Entity\Station::getCategories();

        if ($station_id && $this->getParam('showonlystation', false) == 'true')
        {
            $stations_raw = $this->em->createQuery('SELECT s FROM Entity\Station s WHERE s.id = :station_id')
                ->setParameter('station_id', $station_id)
                ->getArrayResult();
        }
        else
        {
            $stations_raw = $this->em->createQuery('SELECT s FROM Entity\Station s WHERE s.category IN (:types) AND s.is_active = 1 ORDER BY s.weight ASC')
                ->setParameter('types', array('audio', 'video'))
                ->getArrayResult();
        }

        $this->stations = array();
        foreach($stations_raw as $station)
            $this->stations[$station['id']] = $station;

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

        $events_raw = $this->em->createQuery('SELECT s FROM Entity\Schedule s WHERE (s.end_time >= :current AND s.start_time <= :future) ORDER BY s.start_time ASC')
            ->setParameter('current', time())
            ->setParameter('future', strtotime('+1 week'))
            ->getArrayResult();

        $all_events = array();
        $events_by_day = array();

        $num_cols = 3;
        for($i = 0; $i <= $num_cols-1; $i++)
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
            $event['status'] = ($event['start_time'] <= time()) ? 'now' : 'upcoming';
            $event['range'] = \Entity\Schedule::getRangeText($event['start_time'], $event['end_time'], $event['is_all_day']);

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
        $this->doNotRender();
        
        header('Access-Control-Allow-Origin: *');

        $version = $this->_getParam('v', 1);
        $id = $this->_getParam('id');

        $nowplaying = \PVL\NowPlaying::get($version, $id);
        echo json_encode($nowplaying);
    }
}