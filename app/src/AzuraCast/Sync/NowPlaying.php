<?php
namespace AzuraCast\Sync;

use App\Debug;
use Doctrine\ORM\EntityManager;
use Entity;

class NowPlaying extends SyncAbstract
{
    public function run()
    {
        $nowplaying = $this->_loadNowPlaying();

        // Post statistics to InfluxDB.
        $influx = $this->di->get('influx');
        $influx_points = [];

        $total_overall = 0;

        foreach ($nowplaying as $info) {
            $listeners = (int)$info->listeners->current;
            $total_overall += $listeners;

            $station_id = $info->station->id;

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
            $nowplaying[$station]->cache = 'hit';
        }

        /** @var \App\Cache $cache */
        $cache = $this->di->get('cache');
        $cache->save($nowplaying, 'api_nowplaying_data', 120);

        foreach ($nowplaying as $station => $np_info) {
            $nowplaying[$station]->cache = 'database';
        }

        $this->di['em']->getRepository(Entity\Settings::class)
            ->setSetting('nowplaying', $nowplaying);
    }

    /** @var Entity\Repository\SongHistoryRepository */
    protected $history_repo;

    /** @var Entity\Repository\SongRepository */
    protected $song_repo;

    /** @var Entity\Repository\ListenerRepository */
    protected $listener_repo;

    /**
     * @return Entity\Api\NowPlaying[]
     */
    protected function _loadNowPlaying()
    {
        /** @var EntityManager $em */
        $em = $this->di['em'];

        $this->history_repo = $em->getRepository(Entity\SongHistory::class);
        $this->song_repo = $em->getRepository(Entity\Song::class);
        $this->listener_repo = $em->getRepository(Entity\Listener::class);

        $stations = $em->getRepository(Entity\Station::class)->findAll();
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
     * @param Entity\Station $station
     * @return Entity\Api\NowPlaying
     */
    protected function _processStation(Entity\Station $station)
    {
        /** @var EntityManager $em */
        $em = $this->di['em'];

        $np_old = (array)$station->nowplaying_data;

        $np = new Entity\Api\NowPlaying;
        $np->station = $station->api($station->getFrontendAdapter($this->di));

        $frontend_adapter = $station->getFrontendAdapter($this->di);
        $np_raw = $frontend_adapter->getNowPlaying();

        $np->listeners = new Entity\Api\NowPlayingListeners($np_raw['listeners']);

        if (empty($np_raw['current_song']['text'])) {
            $song_obj = $this->song_repo->getOrCreate(['text' => 'Stream Offline'], true);

            $offline_sh = new Entity\Api\SongHistory;
            $offline_sh->sh_id = 0;
            $offline_sh->song = $song_obj->api();
            $np->now_playing = $offline_sh;

            $np->song_history = $this->history_repo->getHistoryForStation($station);
        } else {
            // Pull from current NP data if song details haven't changed.
            $current_song_hash = Entity\Song::getSongHash($np_raw['current_song']);

            if (strcmp($current_song_hash, $np_old['now_playing']['song']['id']) == 0) {
                $np->song_history = $np_old['song_history'];

                $song_obj = $this->song_repo->find($current_song_hash);
            } else {
                $np->song_history = $this->history_repo->getHistoryForStation($station);

                $song_obj = $this->song_repo->getOrCreate($np_raw['current_song'], true);
            }

            // Update detailed listener statistics, if they exist for the station
            if (isset($np_raw['listeners']['clients'])) {
                $this->listener_repo->update($station, $np_raw['listeners']['clients']);
            }

            // Register a new item in song history.
            $sh_obj = $this->history_repo->register($song_obj, $station, $np_raw);
            $np->now_playing = $sh_obj->api();
        }

        $np->cache = 'station';

        $station->nowplaying_data = $np;

        $em->persist($station);
        $em->flush();

        return $np;
    }
}