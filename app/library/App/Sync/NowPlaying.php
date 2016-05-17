<?php
namespace App\Sync;

use Entity\Analytics;
use Entity\Station;
use Entity\Song;
use Entity\SongHistory;
use Entity\Settings;
use App\Debug;

class NowPlaying
{
    public static function sync()
    {
        set_time_limit(60);

        $di = \Phalcon\Di::getDefault();
        $nowplaying = self::loadNowPlaying();

        // Post statistics to InfluxDB.
        $influx = $di->get('influx');

        $total_overall = 0;

        foreach($nowplaying as $short_code => $info)
        {
            $listeners = (int)$info['listeners']['current'];
            $total_overall += $listeners;

            $station_id = $info['station']['id'];
            $influx->insert('station.'.$station_id.'.listeners', [
                'value' => $listeners,
            ]);
        }

        $influx->insert('all.listeners', [
            'value' => $total_overall,
        ]);

        // Generate PVL API cache.
        foreach($nowplaying as $station => $np_info)
            $nowplaying[$station]['cache'] = 'hit';

        $cache = $di->get('cache');
        $cache->save($nowplaying, 'api_nowplaying_data', array('nowplaying'), 60);

        foreach($nowplaying as $station => $np_info)
            $nowplaying[$station]['cache'] = 'database';

        Settings::setSetting('nowplaying', $nowplaying);
    }

    public static function loadNowPlaying()
    {
        Debug::startTimer('Nowplaying Overall');

        $stations = Station::fetchAll();
        $nowplaying = array();

        foreach($stations as $station)
        {
            Debug::startTimer($station->name);

            // $name = $station->short_name;
            $nowplaying[] = self::processStation($station);

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
        $np_old = (array)$station->nowplaying_data;

        $np = array();
        $np['station'] = Station::api($station);

        $frontend_adapter = $station->getFrontendAdapter();
        $np_new = $frontend_adapter->getNowPlaying();
        
        $np = array_merge($np, $np_new);
        $np['listeners'] = $np_new['listeners'];

        // Pull from current NP data if song details haven't changed.
        $current_song_hash = Song::getSongHash($np_new['current_song']);

        if (empty($np['current_song']['text']))
        {
            $np['current_song'] = array();
            $np['song_history'] = $station->getRecentHistory();
        }
        else
        {
            if (strcmp($current_song_hash, $np_old['current_song']['id']) == 0)
            {
                $np['song_history'] = $np_old['song_history'];

                $song_obj = Song::find($current_song_hash);
            }
            else
            {
                $np['song_history'] = $station->getRecentHistory();

                $song_obj = Song::getOrCreate($np_new['current_song'], true);
            }

            // Register a new item in song history.
            $sh_obj = SongHistory::register($song_obj, $station, $np);

            $current_song = Song::api($song_obj);
            $current_song['sh_id'] = $sh_obj->id;

            $np['current_song'] = $current_song;
        }

        $station->nowplaying_data = $np;
        $station->save();

        return $np;
    }
}