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
            /** @var Entity\Station $station */

            Debug::startTimer($station->getName());

            // $name = $station->short_name;
            $nowplaying[] = $this->_processStation($station);

            Debug::endTimer($station->getName());
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

        /** @var Entity\Api\NowPlaying $np_old */
        $np_old = $station->getNowplaying();

        $np = new Entity\Api\NowPlaying;
        $np->station = $station->api($station->getFrontendAdapter($this->di));

        $frontend_adapter = $station->getFrontendAdapter($this->di);
        $np_raw = $frontend_adapter->getNowPlaying();

        $np->listeners = new Entity\Api\NowPlayingListeners($np_raw['listeners']);

        if (empty($np_raw['current_song']['text'])) {
            $song_obj = $this->song_repo->getOrCreate(['text' => 'Stream Offline'], true);

            $offline_sh = new Entity\Api\NowPlayingCurrentSong;
            $offline_sh->sh_id = 0;
            $offline_sh->song = $song_obj->api();
            $np->now_playing = $offline_sh;

            $np->playing_next = null;
            $np->song_history = $this->history_repo->getHistoryForStation($station);
        } else {
            // Pull from current NP data if song details haven't changed.
            $current_song_hash = Entity\Song::getSongHash($np_raw['current_song']);

            if (strcmp($current_song_hash, $np_old->now_playing->song->id) == 0) {
                $song_obj = $this->song_repo->find($current_song_hash);
                $sh_obj = $this->history_repo->register($song_obj, $station, $np_raw);

                $np->song_history = $np_old->song_history;
                $np->playing_next = $np_old->playing_next;
            } else {
                // SongHistory registration must ALWAYS come before the history/nextsong calls
                // otherwise they will not have up-to-date database info!
                $song_obj = $this->song_repo->getOrCreate($np_raw['current_song'], true);
                $sh_obj = $this->history_repo->register($song_obj, $station, $np_raw);

                $np->song_history = $this->history_repo->getHistoryForStation($station);

                $next_song = $this->history_repo->getNextSongForStation($station);

                if ($next_song instanceof Entity\SongHistory) {
                    $np->playing_next = $next_song->api();
                }
            }

            // Update detailed listener statistics, if they exist for the station
            if (isset($np_raw['listeners']['clients'])) {
                $this->listener_repo->update($station, $np_raw['listeners']['clients']);
            }

            // Register a new item in song history.
            $np->now_playing = $sh_obj->api(true);
        }

        $np->cache = 'station';

        $station->setNowplaying($np);

        $em->persist($station);
        $em->flush();

        return $np;
    }
}