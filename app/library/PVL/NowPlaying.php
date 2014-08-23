<?php
namespace PVL;

use \Entity\Statistic;
use \Entity\Schedule;
use \Entity\Station;
use \Entity\StationStream;
use \Entity\Song;
use \Entity\SongHistory;
use \Entity\SongVote;
use \Entity\Settings;

class NowPlaying
{
    public static function generate()
    {
        set_time_limit(60);

        // Run different tasks for different "segments" of now playing data.
        if (!defined('NOWPLAYING_SEGMENT'))
            define('NOWPLAYING_SEGMENT', 1);

        $nowplaying = self::loadNowPlaying();

        // Generate PVL legacy nowplaying file.
        $pvl_file_path = DF_INCLUDE_STATIC.'/api/nowplaying.json';
        $nowplaying_feed = json_encode($nowplaying['legacy'], JSON_UNESCAPED_SLASHES);

        @file_put_contents($pvl_file_path, $nowplaying_feed);

        // Generate PVL API cache.
        $np_api = $nowplaying['api'];
        foreach($np_api as $station => $np_info)
            $np_api[$station]['cache'] = 'hit';

        \DF\Cache::remove('api_nowplaying_data');
        \DF\Cache::save($np_api, 'api_nowplaying_data', array('nowplaying'), 30);

        // Generate PVL API nowplaying file.
        $nowplaying_api = json_encode(array('status' => 'success', 'result' => $np_api), JSON_UNESCAPED_SLASHES);
        $file_path_api = DF_INCLUDE_STATIC.'/api/nowplaying_api.json';

        @file_put_contents($file_path_api, $nowplaying_api);

        // Post statistics to official record.
        Statistic::post($nowplaying['legacy']);

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

            $nowplaying['api'][$name] = self::processStation($station);
            $nowplaying['legacy'][$name] = self::processLegacy($nowplaying['api'][$name]);

            \PVL\Debug::endTimer($station->name);
        }

        \PVL\Debug::endTimer('Nowplaying Overall');

        return $nowplaying;
    }

    /**
     * Generate Structured NowPlaying Data
     *
     * @param Station $station
     * @return array Structured NowPlaying Data
     */
    public static function processStation(Station $station)
    {
        $em = self::getEntityManager();

        $np_old = (array)$station->nowplaying_data;

        $np = array();
        $np['status'] = 'offline';
        $np['station'] = Station::api($station);

        $listener_totals = array(
            'current' => 0,
            'unique' => 0,
            'total' => 0,
        );

        $np['streams'] = array();

        foreach($station->streams as $stream)
        {
            $np_stream = self::processStream($stream, $station);
            $np['streams'][] = $np_stream;

            foreach($np_stream['listeners'] as $type => $count)
                $listener_totals[$type] += $count;

            $em->persist($stream);

            // Merge default info into main array for legacy purposes.
            if ($np_stream['is_default'] == true)
            {
                $np['status'] = $np_stream['status'];

                $np['station']['stream_url'] = $np_stream['url'];
                $np['station']['default_stream_id'] = $np_stream['id'];

                $np['current_song'] = $np_stream['current_song'];
                $np['song_history'] = $np_stream['song_history'];
            }
        }

        $np['listeners'] = $listener_totals;

        // Get currently active event (cached query)
        $event_current = Schedule::getCurrentEvent($station->id);
        $event_upcoming = Schedule::getUpcomingEvent($station->id);

        $np['event'] = Schedule::api($event_current);
        $np['event_upcoming'] = Schedule::api($event_upcoming);

        $station->nowplaying_data = array(
            'current_song'      => $np['current_song'],
            'song_history'      => $np['song_history'],
        );

        $em->persist($station);
        $em->flush();

        return $np;
    }

    /**
     * Process a single stream's NowPlaying info.
     *
     * @param StationStream $stream
     * @param Station $station
     * @return array Structured NowPlaying Data
     */
    public static function processStream(StationStream $stream, Station $station)
    {
        $current_np_data = (array)$stream->nowplaying_data;

        if (!$stream->is_default)
        {
            // Only process non-default streams on odd-numbered "segments" to improve performance.
            if (NOWPLAYING_SEGMENT % 2 == 0 && !empty($current_np_data))
                return $current_np_data;
        }

        $np = array(
            'id'            => $stream->id,
            'name'          => $stream->name,
            'url'           => $stream->stream_url,
            'type'          => $stream->type,
            'is_default'    => $stream->is_default,
        );

        $song_np = array();

        if ($stream->type)
        {
            $custom_class = Station::getStationClassName($station->name);
            $custom_adapter = '\\PVL\\NowPlayingAdapter\\'.$custom_class;

            if (class_exists($custom_adapter))
                $np_adapter = new $custom_adapter($stream, $station);
            elseif ($stream->type == "centovacast")
                $np_adapter = new \PVL\NowPlayingAdapter\CentovaCast($stream, $station);
            elseif ($stream->type == "icecast")
                $np_adapter = new \PVL\NowPlayingAdapter\IceCast($stream, $station);
            elseif ($stream->type == "icebreath")
                $np_adapter = new \PVL\NowPlayingAdapter\IceBreath($stream, $station);
            elseif ($stream->type == "shoutcast2")
                $np_adapter = new \PVL\NowPlayingAdapter\ShoutCast2($stream, $station);
            elseif ($stream->type == "shoutcast1")
                $np_adapter = new \PVL\NowPlayingAdapter\ShoutCast1($stream, $station);
            elseif ($stream->type == "stream")
                $np_adapter = new \PVL\NowPlayingAdapter\Stream($stream, $station);

            \PVL\Debug::log('Adapter Class: '.get_class($np_adapter));

            $song_np = $np_adapter->process();
        }
        else
        {
            $song_np['text'] = 'Error Processing Stream';
            $song_np['is_live'] = false;
            $song_np['status'] = 'offline';
        }

        $np['status'] = $song_np['status'];
        $np['listeners'] = array(
            'current'       => (int)$song_np['listeners'],
            'unique'        => ((isset($song_np['listeners_unique'])) ? (int)$song_np['listeners_unique'] : (int)$song_np['listeners']),
            'total'         => ((isset($song_np['listeners_total'])) ? (int)$song_np['listeners_total'] : (int)$song_np['listeners']),
        );

        // Pull from current NP data if song details haven't changed.
        if (strcmp($song_np['text'], $current_np_data['current_song']['text']) == 0)
        {
            $np['current_song'] = $current_np_data['current_song'];
            $np['song_history'] = $current_np_data['song_history'];
        }
        else if (empty($song_np['text']))
        {
            $np['current_song'] = array();
            $np['song_history'] = $station->getRecentHistory($stream);
        }
        else
        {
            // Send e-mail on the first instance of offline status detected.
            if ($np['text'] == 'Stream Offline')
                self::notifyStation($station, 'offline');

            // Register a new item in song history.
            $np['current_song'] = array();
            $np['song_history'] = $station->getRecentHistory($stream);

            $song_obj = Song::getOrCreate($song_np);
            $sh_obj = SongHistory::register($song_obj, $station, $stream, $np);

            $song_obj->syncExternal();

            // Compose "current_song" object for API.
            $current_song = Song::api($song_obj);
            $current_song['sh_id'] = $sh_obj->id;
            $current_song['score'] = SongVote::getScoreForStation($song_obj, $station);

            $vote_urls = array();
            $vote_functions = array('like', 'dislike', 'clearvote');

            foreach($vote_functions as $vote_function)
            {
                $vote_urls[$vote_function] = \DF\Url::route(array(
                    'module' => 'api',
                    'controller' => 'song',
                    'action' => $vote_function,
                    'sh_id' => $sh_obj->id,
                ));
            }

            $current_song['vote_urls'] = $vote_urls;
            $current_song['external'] = $song_obj->getExternal();

            $np['current_song'] = $current_song;
        }

        $stream->nowplaying_data = $np;

        return $np;
    }

    /**
     * Generate Legacy Now Playing Data
     *
     * @param $np_raw
     * @return array Legacy NowPlaying Data
     */
    public static function processLegacy($np_raw)
    {
        $np = $np_raw['station'];

        $np['code'] = $np['shortcode'];
        $np['web'] = $np['web_url'];
        $np['logo'] = $np['image_url'];
        unset($np['web_url'], $np['image_url'], $np['shortcode']);

        $np['listeners'] = $np_raw['listeners']['current'];
        $np['listeners_unique'] = $np_raw['listeners']['unique'];
        $np['listeners_total'] = $np_raw['listeners']['total'];

        // Merge a default stream info into main array for legacy purposes.
        foreach($np_raw['streams'] as $np_stream)
        {
            if ($np_stream['is_default'] == true)
            {
                $song = $np_stream['current_song'];

                $np['title'] = $song['title'];
                $np['text'] = $song['text'];
                $np['artist'] = $song['artist'];
                $np['song_id'] = $song['id'];
                $np['song_sh_id'] = $song['sh_id'];
                $np['song_score'] = $song['score'];
                $np['song_external'] = $song['external'];

                $np['stream_url'] = $np_stream['url'];

                // Legacy "streams" container.
                $np['streams'] = array(
                    array(
                        'name'      => $np_stream['name'],
                        'url'       => $np_stream['url'],
                    ),
                );

                $np['type'] = $np_stream['type'];
                $np['is_live'] = ($np_stream['status'] == 'online');
                $np['status'] = $np_stream['status'];

                $np['song_history'] = array();
                foreach((array)$np_stream['song_history'] as $hist_row)
                {
                    $row = $hist_row['song'];
                    $row['timestamp'] = $hist_row['played_at'];
                    $np['song_history'][] = $row;
                }
            }
        }

        $np['event'] = $np_raw['event'];
        $np['event_upcoming'] = $np_raw['event_upcoming'];

        return $np;
    }

    /**
     * @return \Doctrine\ORM\EntityManager
     * @throws \Zend_Exception
     */
    public static function getEntityManager()
    {
        static $em;

        if (!$em)
            $em = \Zend_Registry::get('em');

        return $em;
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