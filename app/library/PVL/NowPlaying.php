<?php
namespace PVL;

use \Entity\Statistic;
use \Entity\Schedule;
use \Entity\Station;
use \Entity\Song;
use \Entity\SongHistory;
use \Entity\SongVote;
use \Entity\Settings;

class NowPlaying
{
    public static function generate()
    {
        set_time_limit(60);

        $nowplaying = self::loadNowPlaying();

        // Generate PVL legacy nowplaying file.
        $pvl_file_path = DF_INCLUDE_STATIC.'/api/nowplaying.json';
        $nowplaying_feed = json_encode($nowplaying['legacy'], JSON_UNESCAPED_SLASHES);

        @file_put_contents($pvl_file_path, $nowplaying_feed);

        // Generate PVL API cache.
        \DF\Cache::save($nowplaying['api'], 'api_nowplaying_data', array('nowplaying'), 300);

        // Post statistics to official record.
        Statistic::post($nowplaying);

        return $pvl_file_path;
    }

    public static function loadNowPlaying()
    {
        \PVL\Debug::startTimer('Nowplaying Overall');

        $em = \Zend_Registry::get('em');
        $stations = $em->createQuery('SELECT s FROM Entity\Station s WHERE s.is_active = 1 ORDER BY s.category ASC, s.weight ASC')
            ->execute();

        $nowplaying = array(
            'legacy'    => array(),
            'api'       => array(),
        );

        foreach($stations as $station)
        {
            \PVL\Debug::startTimer($station->name);

            $name = $station->short_name;

            $nowplaying['legacy'][$name] = self::processStation($station);
            $nowplaying['api'][$name] = self::processApi($nowplaying['legacy'][$name], $station);
            
            $em->persist($station);

            \PVL\Debug::endTimer($station->name);
        }

        $em->flush();

        \PVL\Debug::endTimer('Nowplaying Overall');

        return $nowplaying;
    }

    public static function processStation(Station $station)
    {
        $current_np_data = (array)$station->nowplaying_data;

        $np = array(
            'id' => $station->id,
            'category' => $station->category,
            'type' => $station->type,
            'code' => $station->short_name,
            'name' => $station->name,
            'acronym' => $station->acronym,
            'genre' => $station->genre,
            'web' => $station->web_url,
            'player_url' => \DF\Url::route(array('module' => 'default', 'action' => 'tunein', 'id' => $station->id)),
            'logo' => \DF\Url::content($station->image_url, TRUE),
            'streams' => array('name' => 'Primary', 'url' => $station->stream_url),
        );

        if ($station->requests_enabled)
        {
            $request_url = \DF\Url::route(array('module' => 'default', 'controller' => 'station', 'action' => 'request', 'id' => $station->id));
            $np['request_url'] = $request_url;
        }

        if ($station->type)
        {
            $custom_class = Station::getStationClassName($station->name);
            $custom_adapter = '\\PVL\\NowPlayingAdapter\\'.$custom_class;

            if (class_exists($custom_adapter))
                $np_adapter = new $custom_adapter($station);
            elseif ($station->type == "centovacast")
                $np_adapter = new \PVL\NowPlayingAdapter\CentovaCast($station);
            elseif ($station->type == "icecast")
                $np_adapter = new \PVL\NowPlayingAdapter\IceCast($station);
            elseif ($station->type == "shoutcast2")
                $np_adapter = new \PVL\NowPlayingAdapter\ShoutCast2($station);
            elseif ($station->type == "shoutcast1")
                $np_adapter = new \PVL\NowPlayingAdapter\ShoutCast1($station);
            elseif ($station->type == "stream")
                $np_adapter = new \PVL\NowPlayingAdapter\Stream($station);

            \PVL\Debug::log('Adapter Class: '.get_class($np_adapter));

            $np = $np_adapter->process($np);
        }
        else
        {
            $np['text'] = 'Error Processing Stream';
            $np['is_live'] = false;
            $np['status'] = 'offline';
        }

        // Pull from current NP data if song details haven't changed.
        if (strcmp($np['text'], $current_np_data['text']) == 0)
        {
            $np['song_history'] = $current_np_data['song_history'];

            // $np['image'] = $current_np_data['image'];
            $np['song_id'] = $current_np_data['song_id'];
            $np['song_sh_id'] = $current_np_data['song_sh_id'];
            $np['song_score'] = $current_np_data['song_score'];
            $np['song_external'] = $current_np_data['song_external'];
        }
        else if (empty($np['text']))
        {
            $np['song_history'] = $station->getRecentHistory();

            // $np['image'] = $np['logo'];
            $np['song_id'] = NULL;
            $np['song_sh_id'] = NULL;
            $np['song_score'] = 0;
            $np['song_external'] = array();
        }
        else
        {
            // Send e-mail on the first instance of offline status detected.
            if ($np['text'] == 'Stream Offline')
                self::notifyStation($station, 'offline');

            // Register a new item in song history.
            $np['song_history'] = $station->getRecentHistory();

            $song_obj = Song::getOrCreate($np);
            $sh_obj = SongHistory::register($song_obj, $station, $np);

            $song_obj->syncExternal();
            $np['song_external'] = $song_obj->getExternal();

            $np['song_id'] = $song_obj->id;
            $np['song_sh_id'] = $sh_obj->id;
            $np['song_score'] = SongVote::getScoreForStation($song_obj, $station);
        }

        // Get currently active event (cached query)
        $np['event'] = Schedule::getCurrentEvent($station->id);
        $np['event_upcoming'] = Schedule::getUpcomingEvent($station->id);

        $station->nowplaying_data = $np;

        $station->nowplaying_artist = $np['artist'];
        $station->nowplaying_title = $np['title'];
        $station->nowplaying_text = $np['text'];
        $station->nowplaying_listeners = (int)$np['listeners'];

        return $np;
    }

    public static function processApi($np_raw, Station $station)
    {
        $np = array();
        $np['status'] = $np_raw['status'];
        $np['station'] = Station::api($station);

        $np['listeners'] = array(
            'current'       => $np_raw['listeners'],
            'unique'        => (isset($np_raw['listeners_unique'])) ? $np_raw['listeners_unique'] : $np_raw['listeners'],
            'total'         => (isset($np_raw['listeners_total'])) ? $np_raw['listeners_total'] : $np_raw['listeners'],
        );

        $vote_functions = array('like', 'dislike', 'clearvote');
        $vote_urls = array();

        foreach($vote_functions as $vote_function)
            $vote_urls[$vote_function] = \DF\Url::route(array('module' => 'api', 'controller' => 'song', 'action' => $vote_function, 'sh_id' => $np_raw['song_sh_id']));

        $current_song = array(
            'id'        => $np_raw['song_id'],
            'text'      => $np_raw['text'],
            'artist'    => $np_raw['artist'],
            'title'     => $np_raw['title'],

            'score'     => $np_raw['song_score'],
            'sh_id'     => $np_raw['song_sh_id'],
            'vote_urls' => $vote_urls,

            'external'  => $np_raw['song_external'],
        );

        $np['current_song'] = $current_song;

        foreach((array)$np_raw['song_history'] as $song_row)
        {
            $np['song_history'][] = array(
                'played_at' => $song_row['timestamp'],
                'song'      => Song::api($song_row),
            );
        }

        $np['event'] = Schedule::api($np_raw['event']);
        $np['event_upcoming'] = Schedule::api($np_raw['event_upcoming']);

        return $np;
    }

    public static function notifyStation($station, $template)
    {
        if (true || !$station['admin_monitor_station'])
            return false;

        $em = \Zend_Registry::get('em');

        $managers_raw = $em->createQuery('SELECT sm.email FROM Entity\StationManager sm WHERE sm.station_id = :station_id')
            ->setParameter('station_id', $station['id'])
            ->getArrayResult();

        $managers = array();
        foreach($managers_raw as $manager)
            $managers[] = $manager['email'];

        \DF\Messenger::send(array(
            'to'        => $managers,
            'subject'   => 'Station Has Gone Offline',
            'template'  => $template,
            'vars'      => array(
                'station' => $station,
            ),
        ));

        return true;
    }




}