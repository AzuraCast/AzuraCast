<?php
namespace AzuraCast\Sync\Task;

use App\Cache;
use App\Url;
use AzuraCast\ApiUtilities;
use AzuraCast\Radio\Adapters;
use AzuraCast\Webhook\Dispatcher;
use Doctrine\ORM\EntityManager;
use InfluxDB\Database;
use Entity;

class NowPlaying extends TaskAbstract
{
    /** @var EntityManager */
    protected $em;

    /** @var Url */
    protected $url;

    /** @var Database */
    protected $influx;

    /** @var Cache */
    protected $cache;

    /** @var Adapters */
    protected $adapters;

    /** @var Dispatcher */
    protected $webhook_dispatcher;

    /** @var ApiUtilities */
    protected $api_utils;

    /** @var Entity\Repository\SongHistoryRepository */
    protected $history_repo;

    /** @var Entity\Repository\SongRepository */
    protected $song_repo;

    /** @var Entity\Repository\ListenerRepository */
    protected $listener_repo;

    /** @var Entity\Repository\SettingsRepository */
    protected $settings_repo;

    /** @var string */
    protected $analytics_level;

    public function __construct(EntityManager $em, Url $url, Database $influx, Cache $cache, Adapters $adapters,
                                Dispatcher $webhook_dispatcher, ApiUtilities $api_utils)
    {
        $this->em = $em;
        $this->url = $url;
        $this->influx = $influx;
        $this->cache = $cache;
        $this->adapters = $adapters;
        $this->webhook_dispatcher = $webhook_dispatcher;
        $this->api_utils = $api_utils;

        $this->history_repo = $this->em->getRepository(Entity\SongHistory::class);
        $this->song_repo = $this->em->getRepository(Entity\Song::class);
        $this->listener_repo = $this->em->getRepository(Entity\Listener::class);

        $this->settings_repo = $this->em->getRepository(Entity\Settings::class);
        $this->analytics_level = $this->settings_repo->getSetting('analytics', Entity\Analytics::LEVEL_ALL);
    }

    public function run($force = false)
    {
        $nowplaying = $this->_loadNowPlaying($force);

        // Post statistics to InfluxDB.
        if ($this->analytics_level !== Entity\Analytics::LEVEL_NONE) {
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

            $this->influx->writePoints($influx_points, \InfluxDB\Database::PRECISION_SECONDS);
        }

        // Generate API cache.
        foreach ($nowplaying as $station => $np_info) {
            $nowplaying[$station]->cache = 'hit';
        }

        $this->cache->save($nowplaying, 'api_nowplaying_data', 120);

        foreach ($nowplaying as $station => $np_info) {
            $nowplaying[$station]->cache = 'database';
        }

        /** @var Entity\Repository\SettingsRepository $settings_repo */
        $settings_repo = $this->em->getRepository(Entity\Settings::class);

        $settings_repo->setSetting('nowplaying', $nowplaying);
    }

    /**
     * @return Entity\Api\NowPlaying[]
     */
    protected function _loadNowPlaying($force = false)
    {
        $stations = $this->em->getRepository(Entity\Station::class)
            ->findBy(['is_enabled' => 1]);
        $nowplaying = [];

        foreach ($stations as $station) {
            /** @var Entity\Station $station */
            $last_run = $station->getNowplayingTimestamp();

            if ($last_run >= (time()-10) && !$force) {
                $np = $station->getNowplaying();
                $np->update();

                $nowplaying[] = $np;
            } else {
                $nowplaying[] = $this->processStation($station);
            }
        }

        return $nowplaying;
    }

    /**
     * Generate Structured NowPlaying Data for a given station.
     *
     * @param Entity\Station $station
     * @param string|null $payload The request body from the watcher notification service (if applicable).
     * @return Entity\Api\NowPlaying
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     */
    public function processStation(Entity\Station $station, $payload = null)
    {
        $include_clients = ($this->analytics_level === Entity\Analytics::LEVEL_ALL);

        /** @var Entity\Api\NowPlaying|null $np_old */
        $np_old = $station->getNowplaying();

        $np = new Entity\Api\NowPlaying;

        $frontend_adapter = $this->adapters->getFrontendAdapter($station);

        $np->station = $station->api($frontend_adapter);
        $np_raw = $frontend_adapter->getNowPlaying($payload, $include_clients);

        $np->listeners = new Entity\Api\NowPlayingListeners($np_raw['listeners']);

        if (empty($np_raw['current_song']['text'])) {
            $song_obj = $this->song_repo->getOrCreate(['text' => 'Stream Offline'], true);

            $offline_sh = new Entity\Api\NowPlayingCurrentSong;
            $offline_sh->sh_id = 0;
            $offline_sh->song = $song_obj->api($this->api_utils);
            $np->now_playing = $offline_sh;

            $np->song_history = $this->history_repo->getHistoryForStation($station, $this->api_utils);

            $next_song = $this->history_repo->getNextSongForStation($station);
            if ($next_song instanceof Entity\SongHistory) {
                $np->playing_next = $next_song->api($this->api_utils);
            } else {
                $np->playing_next = null;
            }

            $np->live = new Entity\Api\NowPlayingLive(false);
        } else {
            // Pull from current NP data if song details haven't changed.
            $current_song_hash = Entity\Song::getSongHash($np_raw['current_song']);

            if ($np_old instanceof Entity\Api\NowPlaying && strcmp($current_song_hash, $np_old->now_playing->song->id) === 0) {
                /** @var Entity\Song $song_obj */
                $song_obj = $this->song_repo->find($current_song_hash);

                $sh_obj = $this->history_repo->register($song_obj, $station, $np_raw);

                $np->song_history = $np_old->song_history;
                $np->playing_next = $np_old->playing_next;
            } else {
                // SongHistory registration must ALWAYS come before the history/nextsong calls
                // otherwise they will not have up-to-date database info!
                $song_obj = $this->song_repo->getOrCreate($np_raw['current_song'], true);
                $sh_obj = $this->history_repo->register($song_obj, $station, $np_raw);

                $np->song_history = $this->history_repo->getHistoryForStation($station, $this->api_utils);

                $next_song = $this->history_repo->getNextSongForStation($station);

                if ($next_song instanceof Entity\SongHistory) {
                    $np->playing_next = $next_song->api($this->api_utils);
                }
            }

            // Update detailed listener statistics, if they exist for the station
            if ($include_clients && isset($np_raw['listeners']['clients'])) {
                $this->listener_repo->update($station, $np_raw['listeners']['clients']);
            }

            // Detect and report live DJ status
            if ($station->getIsStreamerLive()) {
                $current_streamer = $station->getCurrentStreamer();
                $streamer_name = ($current_streamer instanceof Entity\StationStreamer)
                    ? $current_streamer->getDisplayName()
                    : 'Live DJ';

                $np->live = new Entity\Api\NowPlayingLive(true, $streamer_name);
            } else {
                $np->live = new Entity\Api\NowPlayingLive(false, '');
            }

            // Register a new item in song history.
            $np->now_playing = $sh_obj->api($this->api_utils, true);
        }

        $np->update();
        $np->cache = 'station';

        $station->setNowplaying($np);

        $this->em->persist($station);
        $this->em->flush();

        $np_old = ($np_old instanceof Entity\Api\NowPlaying) ? $np_old : $np;
        $this->webhook_dispatcher->dispatch($station, $np_old, $np, ($payload !== null));

        return $np;
    }
}