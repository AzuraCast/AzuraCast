<?php

class UtilController extends \DF\Controller\Action
{
	public function indexAction()
	{
        $this->doNotRender();
        
        phpinfo();
	}

	public function testAction()
	{
		$this->doNotRender();

        error_reporting(E_ALL & ~E_NOTICE);
        ini_set('display_errors', 1);

        \PVL\CentovaCast::sync();

        echo 'CC Synced';

        exit;

        $results = \Entity\StationMedia::search(1, 'Lost on the');
        \DF\Utilities::print_r($results);
        exit;

        /*
        $station = \Entity\Station::find(1);
        $track_id = 4751;

        echo \PVL\CentovaCast::request($station, $track_id);
        echo 'Requested';
        */

        $tzlist = DateTimeZone::listIdentifiers(DateTimeZone::ALL);

        \DF\Utilities::print_r($tzlist);

        exit;

        $start_threshold = time();
        $end_threshold = time()+(60*150);

        $schedule_items = $this->em->createQuery('SELECT s, st FROM Entity\Schedule s JOIN s.station st WHERE s.start_time >= :start AND s.start_time <= :end AND s.type = :type AND s.is_notified = 0')
            ->setParameter('start', $start_threshold)
            ->setParameter('end', $end_threshold)
            ->setParameter('type', 'station')
            ->setMaxResults(1)
            ->getArrayResult();

        \DF\Utilities::print_r($schedule_items);

        \PVL\NotificationManager::run();

        \PVL\Debug::printLog();
        echo 'Done';

        exit;

        $config = \Zend_Registry::get('config');

        $twitter = new \tmhOAuth(array(
            'consumer_key'               => 'WLqDn5dtj6j8x0dniSDA',
            'consumer_secret'            => 'sIg2a28G9qVPdy9AvSdJrvNBzqpLeE1exCqyndxmE',
            'user_token'                 => '2295961248-4CAsZTXrDyGIjVY4wi7j2oYG83qiMe0SkUw6u6S',
            'user_secret'                => 'rB8QtWtqsjthSQ67TLIB018tqqgOFYeQ1EZ1wRYBCATvw',
        ));

        $message = '@PonyvilleLive Test message 2 Test 2 message whaaa';

        $twitter->request('POST', 'https://api.twitter.com/1.1/statuses/update.json', array(
            'status' => substr($message, 0, 140),
        ));

        $response_raw = $twitter->response['response'];
        \DF\Utilities::print_r($response_raw);

        exit;

        \PVL\ScheduleManager::run(TRUE);
        \PVL\Debug::printLog();
        exit;



        \DF\Utilities::print_r($news);

        exit;

        $api_params = array(
            'api_key'       => 'Hp1W4lpJ0dhHA7pOGih0yow02ZXAFHdiIR5bzFS67C0xlERPAZ',
            'limit'         => 5,
        );
        $api_url = 'http://api.tumblr.com/v2/blog/news.ponyvillelive.com/posts/photo?'.http_build_query($api_params);

        $results = file_get_contents($api_url);
        \DF\Utilities::print_r(json_decode($results));

        echo 'Finished all stats';
        exit;
        

        // Google Calendar Thingy for West

        $schedule_url = 'https://www.google.com/calendar/feeds/del6kl5lj0de5j2gg6ot8vjjhs%40group.calendar.google.com/private-f42884cbe5be8fb97af30e184e3ba202/full';

        $start_time = date(\DateTime::RFC3339);
        $end_time = date(\DateTime::RFC3339, strtotime('+1 week'));
        $http_params = array(
            'alt'           => 'json',
            'recurrence-expansion-start' => $start_time,
            'recurrence-expansion-end' => $end_time,
            'start-min'     => $start_time,
            'start-max'     => $end_time,
            'max-results'   => 200,
            'singleevents'  => 'true',
            'orderby'       => 'starttime',
            'sortorder'     => 'ascending',
            'ctz'           => date_default_timezone_get(),
        );

        $base_url = $schedule_url;
        $http_url = str_replace('/basic', '/full', $base_url).'?'.http_build_query($http_params);
        $calendar_raw = @file_get_contents($http_url);
        $calendar = array();

        if ($calendar_raw)
        {
            $calendar_array = json_decode($calendar_raw, true);
            $events = (array)$calendar_array['feed']['entry'];

            if ($debug_mode)
                \DF\Utilities::print_r($calendar_array);

            $all_events = array();

            foreach($events as $event_orig)
            {
                $title = trim($event_orig['title']['$t']);
                $body = trim($event_orig['content']['$t']);
                $location = trim($event_orig['gd$where'][0]['valueString']);
                $web_url = trim($event_orig['link'][0]['href']);

                $is_all_day = false;

                $start_time = trim($event_orig['gd$when'][0]['startTime']);
                if (strlen($start_time) == 10)
                {
                    $is_all_day = true;
                    $start_time = strtotime($start_time.' 00:00:00');
                }
                else
                {
                    $start_time = strtotime($start_time);
                }

                $end_time = trim($event_orig['gd$when'][0]['endTime']);
                if (strlen($end_time) == 10)
                {
                    $is_all_day = true;
                    $end_time = strtotime($end_time.' 00:00:00')-1;
                }
                else
                {
                    $end_time = strtotime($end_time);
                }

                $start_datetime = new DateTime('@'.$start_time, new \DateTimeZone('America/New_York'));
                $end_datetime = new DateTime('@'.$start_time, new \DateTimeZone('America/New_York'));

                if ($start_time > time())
                {
                    $calendar[] = array(
                        'title'     => $title,
                        'start_time' => $start_time,
                        'start_time_text' => $start_datetime->format('F j, Y g:ia'),
                        'end_time'  => $end_time,
                        'end_time_text' => $end_datetime->format('F j, Y g:ia'),
                        'is_all_day' => (int)$is_all_day,
                        'gcal_url'   => $web_url,
                    );
                }
            }
        }

        $events_to_show = array_slice($calendar, 0, 3);
        \DF\Utilities::print_r($events_to_show);

        exit;

        $song_history = $this->em->createQuery('SELECT sh.song_id FROM Entity\SongHistory sh WHERE sh.station_id = :station_id ORDER BY sh.timestamp DESC')
            ->setMaxResults(1)
            ->setParameter('station_id', 2)
            ->getSingleScalarResult();

        echo $song_history;

        // \PVL\NowPlaying::generate();

        exit;

        $return_raw = @file_get_contents('http://molestia.ponify.me/status.xsl?mount=/autodj');

        $temp_array = array();
        $search_for = "<td\s[^>]*class=\"streamdata\">(.*)<\/td>";
        $search_td = array('<td class="streamdata">','</td>');

        if(preg_match_all("/$search_for/siU", $return_raw, $matches)) 
        {
            foreach($matches[0] as $match) 
            {
                $to_push = str_replace($search_td,'',$match);
                $to_push = trim($to_push);
                array_push($temp_array,$to_push);
            }
        }

        \DF\Utilities::print_r($temp_array);

        $temp_array = array_slice($temp_array, -10);
        
        \DF\Utilities::print_r($temp_array);
        exit;

        $this->em->createQuery('DELETE FROM Entity\Schedule s')->execute();
        \Entity\Schedule::resetAutoIncrement();
        exit;

        $path_info = parse_url('http://lunaradio.hachisoftware.com/blog/post/5');
        $uri = $path_info['path'];
        $post_num = (int)array_pop(explode('/', $uri));

        $uri_parts = explode('/', $uri);
        echo $uri_parts[2];
        exit;

        /*
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        */

        \PVL\CacheManager::generateSlimPlayer();

        exit;

        /*
        $artists = $this->em->createQuery('SELECT a FROM Entity\Artist a ORDER BY a.id ASC')
            ->execute();

        foreach($artists as $artist)
        {
            if ($artist->web_url)
            {
                $web_url = trim($artist->web_url);

                if (stristr($web_url, 'twitter.com') !== FALSE)
                    $artist->twitter_url = $web_url;
                else if (stristr($web_url, 'tumblr.com') !== FALSE)
                    $artist->tumblr_url = $web_url;
                else if (stristr($web_url, 'soundcloud.com') !== FALSE)
                    $artist->soundcloud_url = $web_url;
                else if (stristr($web_url, 'facebook.com') !== FALSE)
                    $artist->facebook_url = $web_url;
                else if (stristr($web_url, 'youtube.com') !== FALSE)
                    $artist->youtube_url = $web_url;
                else if (stristr($web_url, 'deviantart.com') !== FALSE)
                    $artist->deviantart_url = $web_url;

                $this->em->persist($artist);
            }
        }

        $this->em->flush();

        echo 'Done';
        return;
        */

        \PVL\ArtistNews::run();

        echo 'Done';
        return;

        $item = \Entity\Schedule::find(215853);

        $config = \Zend_Registry::get('config');

        $twitter = new \tmhOAuth($config->twitter->toArray());

        $tunein_url = \DF\Url::route(array(
            'controller' => 'index',
            'action' => 'index',
            'id' => $item['station']['id'],
        ));
        $message = 'In 30 minutes: "'.$item['title'].'" on '.$item['station']['name'].' - Tune in now at '.$tunein_url;

        $twitter->request('POST', 'https://api.twitter.com/1.1/statuses/update.json', array(
            'status' => $message,
        ));

        exit;

        \PVL\NewsManager::run(true);

        exit;

        $http = new \Zend_Http_Client();
        $http->setConfig(array(
            'timeout'       => 60,
            'keepalive'     => true,
        ));

        $http->setUri('http://96.47.231.103:8080/erlyvideo/api/streams');
        $http->setAuth('admin', 'pass0');

        $response = $http->request('GET');
        echo $response->getBody();
        return;

        /*
        $type = \Entity\EventType::getRepository()->findOneByName('Convention');

        $events = \Entity\Event::fetchAll();
        foreach($events as $event)
        {
            $event->types->add($type);
            $event->save();
        }

        echo 'Done';
        return;
        */

		$types_raw = array(
            'music' => array(
                'name'      => 'Musician',
                'icon'      => 'icon-music',
            ),
            'animator' => array(
                'name'      => 'Animator',
                'icon'      => 'icon-magic',
            ),
            'artist' => array(
                'name'      => 'Graphic Artist',
                'icon'      => 'icon-picture',
            ),
            'va' => array(
                'name'      => 'Voice Actor/Actress',
                'icon'      => 'icon-bullhorn',
            ),
            'podcast' => array(
                'name'      => 'Podcaster',
                'icon'      => 'icon-rss',
            ),
            'dj' => array(
                'name'      => 'DJ',
                'icon'      => 'icon-headphones',
            ),
            'writer' => array(
                'name'      => 'Writer',
                'icon'      => 'icon-file',
            ),
            'other' => array(
                'name'      => 'Other',
                'icon'      => 'icon-star',
            ),
        );

		$type_lookup = array();
		foreach($types_raw as $type_key => $type_info)
		{
			$record = new \Entity\ArtistType;
			$record->name = $type_info['name'];
			$record->icon = $type_info['icon'];
			$record->save();

			$type_lookup[$type_key] = $record;
		}

		$artists = \Entity\Artist::fetchAll();

		foreach($artists as $artist)
		{
			$type = $artist->type;

			$artist->types->add($type_lookup[$type]);
			$artist->save();
		}

		echo 'Done';
	}

    public function infoAction()
    {
        $this->doNotRender();
        phpinfo();
    }
}