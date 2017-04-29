<?php
namespace AzuraCast\Sync;

use App\Debug;
use Doctrine\ORM\EntityManager;
use Entity\Song;
use Entity\SongHistory;
use Entity\Station;

class NowPlaying extends SyncAbstract
{
    public function run()
    {
        $nowplaying = $this->_loadNowPlaying();

        // Post statistics to InfluxDB.
        $influx = $this->di->get('influx');
        $influx_points = [];

        $total_overall = 0;

        foreach ($nowplaying as $short_code => $info) {
            $listeners = (int)$info['listeners']['current'];
            $total_overall += $listeners;

            $station_id = $info['station']['id'];

            $influx_points[] = new \InfluxDB\Point(
                'station.' . $station_id . '.listeners',
                $listeners,
                [],
                ['station' => $station_id],
                time()
            );
        }

        $influx_points[] = new \InfluxDB\Point(
            'station.all.listeners',
            $total_overall,
            [],
            ['station' => 0],
            time()
        );

        $influx->writePoints($influx_points, \InfluxDB\Database::PRECISION_SECONDS);

        // Generate API cache.
        foreach ($nowplaying as $station => $np_info) {
            $nowplaying[$station]['cache'] = 'hit';
        }

        $cache = $this->di->get('cache');
        $cache->save($nowplaying, 'api_nowplaying_data', ['nowplaying'], 60);

        foreach ($nowplaying as $station => $np_info) {
            $nowplaying[$station]['cache'] = 'database';
        }

        $this->di['em']->getRepository('Entity\Settings')->setSetting('nowplaying', $nowplaying);
    }

    protected function _loadNowPlaying()
    {
        /** @var EntityManager $em */
        $em = $this->di['em'];
        $stations = $em->getRepository(Station::class)->findAll();

        $nowplaying = [];

        foreach ($stations as $station) {
            Debug::startTimer($station->name);

            // $name = $station->short_name;
            $nowplaying[] = $this->_processStation($station);

            Debug::endTimer($station->name);
        }

        return $nowplaying;
    }

    /**
     * Generate Structured NowPlaying Data
     *
     * @param Station $station
     * @return array Structured NowPlaying Data
     */
    protected function _processStation(Station $station)
    {
        /** @var EntityManager $em */
        $em = $this->di['em'];

        $np_old = (array)$station->nowplaying_data;

        $np = [];
        $np['station'] = Station::api($station, $this->di);

        $frontend_adapter = $station->getFrontendAdapter($this->di);
        $np_new = $frontend_adapter->getNowPlaying();

        $np = array_merge($np, $np_new);
        $np['listeners'] = $np_new['listeners'];

        // Pull from current NP data if song details haven't changed.
        $current_song_hash = Song::getSongHash($np_new['current_song']);

        if (empty($np['current_song']['text'])) {
            $np['current_song'] = [];
            $np['song_history'] = $em->getRepository(SongHistory::class)->getHistoryForStation($station);
        } else {
            if (strcmp($current_song_hash, $np_old['current_song']['id']) == 0) {
                $np['song_history'] = $np_old['song_history'];

                $song_obj = $em->getRepository(Song::class)->find($current_song_hash);
            } else {
                $np['song_history'] = $em->getRepository(SongHistory::class)->getHistoryForStation($station);

                $song_obj = $em->getRepository(Song::class)->getOrCreate($np_new['current_song'], true);
            }

            // Register a new item in song history.
            $sh_obj = $em->getRepository(SongHistory::class)->register($song_obj, $station, $np);

            $current_song = Song::api($song_obj);
            $current_song['sh_id'] = $sh_obj->id;

            $np['current_song'] = $current_song;
        }

        $station->nowplaying_data = $np;

        $em->persist($station);
        $em->flush();

        return $np;
    }
}