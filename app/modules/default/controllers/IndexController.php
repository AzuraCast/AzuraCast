<?php
use \Entity\Station;
use \Entity\Artist;
use \Entity\Podcast;
use \Entity\Event;
use \Entity\NetworkNews;
use \Entity\Settings;
use \Entity\Schedule;
use \Entity\Rotator;

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
        $conventions = Schedule::getUpcomingConventions();
        $this->view->conventions = $conventions;

        // Pull rotators.
        $rotators = Rotator::fetch();
        $this->view->rotators = $rotators;

        // Special event flagging and special formatting.
        $special_event = (Settings::getSetting('special_event', 0) == 1);
        $this->view->special_event = $special_event;

        if ($special_event)
        {
            $autoplay_station = Settings::getSetting('special_event_station_id', 0);

            if ($autoplay_station != 0)
            {
                $this->view->station_id = $autoplay_station;
                $this->view->autoplay = true;
            }
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
        // $this->render('index_stations');
    }

    public function chatAction()
    {
        $this->view->layout()->setLayout('maintenance');
        $this->view->skin = $this->_getParam('skin', 'dark');

        $this->_initStations();
    }

    public function appAction()
    {}

    public function likeAction()
    {
        $this->doNotRender();

        if (!$this->acl->isAllowed('is logged in'))
        {
            echo 'NOTLOGGEDIN';
            return;
        }
        else
        {
            $song_id = $this->_getParam('id');
            $song = \Entity\Song::find($song_id);

            if ($song instanceof \Entity\Song)
            {
                $user = $this->auth->getLoggedInUser();
                $song->like($user);

                echo 'OK';
                return;
            }
        }

        echo 'ERROR';
        return;
    }

    /**
     * Protected Functions
     */

    protected $stations;
    protected $categories;

    protected function _initStations()
    {
        $this->view->station_id = $station_id = $this->_getParam('id', NULL);
        $this->view->volume = ($this->_hasParam('volume')) ? (int)$this->_getParam('volume') : 30;

        $this->categories = \Entity\Station::getCategories();

        /*
        $special_event = Settings::getSetting('special_event', 0);
        if (!$special_event)
            unset($this->categories['event']);
        */

        if ($station_id && $this->_getParam('showonlystation', false) == 'true')
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

        /**
         * Compose events
         */

        $events = array();
        
        $events_now = $this->em->createQuery('SELECT s FROM Entity\Schedule s WHERE s.type = :type AND s.start_time <= :current AND s.end_time >= :current ORDER BY s.start_time ASC')
            ->setParameter('type', 'station')
            ->setParameter('current', time())
            ->getArrayResult();

        foreach((array)$events_now as $event)
        {
            $event['status'] = 'now';
            $events[] = $event;
        }

        $events_upcoming = $this->em->createQuery('SELECT s FROM Entity\Schedule s WHERE s.type = :type AND s.start_time > :current AND s.start_time <= :future ORDER BY s.start_time ASC')
            ->setParameter('type', 'station')
            ->setParameter('current', time())
            ->setParameter('future', strtotime('+1 week'))
            ->getArrayResult();

        foreach((array)$events_upcoming as $event)
        {
            $event['status'] = 'upcoming';
            $events[] = $event;
        }

        $all_events = array();
        $today = date('Y-m-d');

        foreach($events as $event)
        {
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

            if (date('Y-m-d', $event['start_time']) == $today || $event['status'] == 'now')
                $event['is_today'] = true;
            else
                $event['is_today'] = false;

            $all_events[] = $event;
        }

        $top_events = array_slice($all_events, 0, 20);
        $events_by_day = array(
            'Today' => array(),
        );
        
        foreach($all_events as $event)
        {
            $event_date = date('Y-m-d', $event['start_time']);

            if ($event['is_today'])
                $events_by_day['Today'][] = $event;
            
            $events_by_day[$event_date][] = $event;
        }

        if (count($events_by_day['Today']) == 0)
            unset($events_by_day['Today']);

        foreach($this->stations as $station)
        {
            if (isset($this->categories[$station['category']]))
                $this->categories[$station['category']]['stations'][] = $station;
        }

        $this->view->events_by_day = $events_by_day;
        $this->view->all_events = $all_events;
        $this->view->top_events = $top_events;

        $this->view->events = $events;
        $this->view->stations = $this->stations;
        $this->view->categories = $this->categories;
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