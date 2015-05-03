<?php
namespace PVL;

use Entity\Analytics;
use Entity\Statistic;
use Entity\Schedule;
use Entity\Station;
use Entity\StationStream;
use Entity\Song;
use Entity\SongHistory;
use Entity\SongVote;
use Entity\Settings;

use DF\Cache;

use PVL\Debug;
use PVL\Service\PvlNode;

class NowPlaying
{
    public static function generate()
    {
        set_time_limit(60);

        // Fix DF\URL // prefixing.
        \DF\Url::forceSchemePrefix(true);

        $nowplaying = self::loadNowPlaying();

        // Post statistics to official record (legacy for duplication, for now)
        // Analytics::post($nowplaying['api']);

        // Post statistics to InfluxDB.
        $influx = self::getInflux();
        $influx->setDatabase('pvlive_stations');

        $active_shortcodes = Station::getShortNameLookup();
        $total_overall = 0;

        foreach($nowplaying['api'] as $short_code => $info)
        {
            $listeners = (int)$info['listeners']['current'];
            $station_id = $info['station']['id'];

            if (isset($active_shortcodes[$short_code]))
                $total_overall += $listeners;

            $influx->insert('station.'.$station_id.'.listeners', [
                'value' => $listeners,
            ]);
        }

        $influx->insert('all.listeners', [
            'value' => $total_overall,
        ]);

        // Clear any records that are not audio/video category.
        $api_categories = array('audio', 'video');
        foreach($nowplaying['api'] as $station_shortcode => $station_info)
        {
            if (!in_array($station_info['station']['category'], $api_categories))
            {
                unset($nowplaying['api'][$station_shortcode]);
                unset($nowplaying['legacy'][$station_shortcode]);
            }
        }

        // Generate PVL legacy nowplaying file.
        $pvl_file_path = DF_INCLUDE_STATIC.'/api/nowplaying.json';
        $nowplaying_feed = json_encode($nowplaying['legacy'], JSON_UNESCAPED_SLASHES);

        @file_put_contents($pvl_file_path, $nowplaying_feed);

        // Generate PVL API cache.
        $np_api = $nowplaying['api'];
        foreach($np_api as $station => $np_info)
            $np_api[$station]['cache'] = 'hit';

        Cache::save($np_api, 'api_nowplaying_data', array('nowplaying'), 60);

        foreach($np_api as $station => $np_info)
            $np_api[$station]['cache'] = 'flatfile';

        // Generate PVL API nowplaying file.
        $nowplaying_api = json_encode(array('status' => 'success', 'result' => $np_api), JSON_UNESCAPED_SLASHES);
        $file_path_api = DF_INCLUDE_STATIC.'/api/nowplaying_api.json';

        @file_put_contents($file_path_api, $nowplaying_api);

        // Push to live-update service.
        PvlNode::push('nowplaying', $nowplaying['api']);

        return $pvl_file_path;
    }

    public static function loadNowPlaying()
    {
        Debug::startTimer('Nowplaying Overall');

        $em = self::getEntityManager();

        $stations = Station::fetchAll();

        $nowplaying = array(
            'legacy'    => array(),
            'api'       => array(),
        );

        foreach($stations as $station)
        {
            Debug::startTimer($station->name);

            $name = $station->short_name;

            $nowplaying['api'][$name] = self::processStation($station);
            $nowplaying['legacy'][$name] = self::processLegacy($nowplaying['api'][$name]);

            Debug::endTimer($station->name);
            Debug::divider();
        }

        Debug::endTimer('Nowplaying Overall');

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

        // Remove API-supplied 'streams' item in the wrong place.
        unset($np['station']['streams']);

        $listener_totals = array(
            'current' => 0,
            'unique' => 0,
            'total' => 0,
        );

        $np['streams'] = array();

        foreach($station->streams as $stream)
        {
            if (!$stream->is_active)
                continue;

            if ($station->category == 'video')
            {
                $np_stream = self::processVideoStream($stream, $station);

                foreach($listener_totals as $type => $total)
                    $listener_totals[$type] += $np_stream['meta']['listeners'];
            }
            else
            {
                $np_stream = self::processAudioStream($stream, $station);

                foreach ($np_stream['listeners'] as $type => $count)
                    $listener_totals[$type] += $count;
            }

            $np['streams'][] = $np_stream;

            $em->persist($stream);

            // Merge default info into main array for legacy purposes.
            if ($np_stream['is_default'] == true)
            {
                $np['status'] = $np_stream['status'];

                $np['station']['stream_url'] = $np_stream['url'];
                $np['station']['default_stream_id'] = $np_stream['id'];

                if ($station->category != 'video')
                {
                    $np['current_song'] = $np_stream['current_song'];
                    $np['song_history'] = $np_stream['song_history'];
                }
            }
        }

        $np['listeners'] = $listener_totals;

        // Get currently active event (cached query)
        $event_current = Schedule::getCurrentEvent($station->id);
        $event_upcoming = Schedule::getUpcomingEvent($station->id);

        $np['event'] = Schedule::api($event_current);
        $np['event_upcoming'] = Schedule::api($event_upcoming);

        if ($station->category != 'video')
        {
            $station->nowplaying_data = array(
                'current_song' => $np['current_song'],
                'song_history' => $np['song_history'],
            );
            $em->persist($station);
        }

        $em->flush();

        return $np;
    }

    /**
     * Process a single audio stream's NowPlaying info.
     *
     * @param StationStream $stream
     * @param Station $station
     * @return array Structured NowPlaying Data
     */
    public static function processAudioStream(StationStream $stream, Station $station, $force = false)
    {
        $current_np_data = (array)$stream->nowplaying_data;

        // Only process non-default streams on odd-numbered "segments" to improve performance.
        if (!$stream->is_default && !$force && (NOWPLAYING_SEGMENT % 2 == 0) && !empty($current_np_data))
            return $current_np_data;

        $np = StationStream::api($stream);

        $custom_class = Station::getStationClassName($station->name);
        $custom_adapter = '\\PVL\\RadioAdapter\\'.$custom_class;

        if (class_exists($custom_adapter))
            $np_adapter = new $custom_adapter($stream, $station);
        elseif ($stream->type == "icecast")
            $np_adapter = new \PVL\RadioAdapter\IceCast($stream, $station);
        elseif ($stream->type == "icebreath")
            $np_adapter = new \PVL\RadioAdapter\IceBreath($stream, $station);
        elseif ($stream->type == "shoutcast2")
            $np_adapter = new \PVL\RadioAdapter\ShoutCast2($stream, $station);
        elseif ($stream->type == "shoutcast1")
            $np_adapter = new \PVL\RadioAdapter\ShoutCast1($stream, $station);
        else
            return array();

        Debug::log('Adapter Class: '.get_class($np_adapter));

        $stream_np = $np_adapter->process();

        $np = array_merge($np, $stream_np['meta']);
        $np['listeners'] = $stream_np['listeners'];

        // Pull from current NP data if song details haven't changed.
        $current_song_hash = Song::getSongHash($stream_np['current_song']);

        if (strcmp($current_song_hash, $current_np_data['current_song']['id']) == 0)
        {
            $np['current_song'] = $current_np_data['current_song'];
            $np['song_history'] = $current_np_data['song_history'];
        }
        else if (empty($stream_np['current_song']['text']))
        {
            $np['current_song'] = array();
            $np['song_history'] = $station->getRecentHistory($stream);
        }
        else
        {
            // Register a new item in song history.
            $np['current_song'] = array();
            $np['song_history'] = $station->getRecentHistory($stream);

            // Determine whether to log this song play for analytics.
            $log_radio_play = ($stream->is_default && $station->category == 'audio');

            $song_obj = Song::getOrCreate($stream_np['current_song'], $log_radio_play);
            $sh_obj = SongHistory::register($song_obj, $station, $stream, $np);

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

            $external = $song_obj->getExternal();
            if ($external)
                $current_song['external'] = $song_obj->getExternal();

            $np['current_song'] = $current_song;
        }

        $stream->nowplaying_data = $np;
        return $np;
    }

    /**
     * Process a single video stream's NowPlaying info.
     *
     * @param StationStream $stream
     * @param Station $station
     * @return array Structured NowPlaying Data
     */
    public static function processVideoStream(StationStream $stream, Station $station, $force = false)
    {
        $current_np_data = (array)$stream->nowplaying_data;

        if (!$force && (NOWPLAYING_SEGMENT % 2 == 0) && !empty($current_np_data))
            return $current_np_data;

        // Process stream.
        $custom_class = Station::getStationClassName($station->name);
        $custom_adapter = '\\PVL\\VideoAdapter\\'.$custom_class;

        $np = StationStream::api($stream);

        if (class_exists($custom_adapter))
        {
            $np_adapter = new $custom_adapter($stream, $station);
            $stream_np = $np_adapter->process();
        }
        else
        {
            $adapters = array(
                new \PVL\VideoAdapter\Livestream($stream, $station),
                new \PVL\VideoAdapter\TwitchTv($stream, $station),
                new \PVL\VideoAdapter\UStream($stream, $station),
                new \PVL\VideoAdapter\StreamUp($stream, $station),
            );

            foreach($adapters as $np_adapter)
            {
                if ($np_adapter->canHandle())
                {
                    $stream_np = $np_adapter->process();
                    break;
                }
            }
        }

        if (!empty($stream_np))
        {
            $np = array_merge($np, $stream_np);
            $np['status'] = (isset($np['meta']['status'])) ? $np['meta']['status'] : 'offline';

            Debug::log('Adapter Class: ' . get_class($np_adapter));
            Debug::print_r($np);
        }
        else
        {
            $np['on_air'] = array('text' => 'Stream Offline');
            $np['meta'] = array(
                'status' => 'offline',
                'listeners' => 0,
            );
            $np['status'] = 'offline';
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

        if ($np_raw['station']['category'] == 'video')
            return $np;

        // Merge a default stream info into main array for legacy purposes.
        foreach($np_raw['streams'] as $np_stream)
        {
            if (!$np_stream['is_default'])
                continue;

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
        $di = \Phalcon\Di::getDefault();
        return $di->get('em');
    }

    public static function getInflux()
    {
        $di = \Phalcon\Di::getDefault();
        return $di->get('influx');
    }
}