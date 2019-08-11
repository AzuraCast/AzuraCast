<?php
namespace App\Sync\Task;

use App\ApiUtilities;
use App\Entity;
use App\Event\Radio\GenerateRawNowPlaying;
use App\Event\SendWebhooks;
use App\Message;
use App\MessageQueue;
use App\Radio\Adapters;
use App\Radio\AutoDJ;
use Azura\EventDispatcher;
use Doctrine\ORM\EntityManager;
use GuzzleHttp\Psr7\Uri;
use InfluxDB\Database;
use Monolog\Logger;
use NowPlaying\Adapter\AdapterAbstract;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use function DeepCopy\deep_copy;

class NowPlaying extends AbstractTask implements EventSubscriberInterface
{
    /** @var Database */
    protected $influx;

    /** @var CacheInterface */
    protected $cache;

    /** @var Adapters */
    protected $adapters;

    /** @var AutoDJ */
    protected $autodj;

    /** @var EventDispatcher */
    protected $event_dispatcher;

    /** @var MessageQueue */
    protected $message_queue;

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

    /**
     * @param EntityManager $em
     * @param Logger $logger
     * @param Adapters $adapters
     * @param ApiUtilities $api_utils
     * @param AutoDJ $autodj
     * @param CacheInterface $cache
     * @param Database $influx
     * @param EventDispatcher $event_dispatcher
     * @param MessageQueue $message_queue
     */
    public function __construct(
        EntityManager $em,
        Logger $logger,
        Adapters $adapters,
        ApiUtilities $api_utils,
        AutoDJ $autodj,
        CacheInterface $cache,
        Database $influx,
        EventDispatcher $event_dispatcher,
        MessageQueue $message_queue)
    {
        parent::__construct($em, $logger);

        $this->adapters = $adapters;
        $this->api_utils = $api_utils;
        $this->autodj = $autodj;
        $this->cache = $cache;
        $this->event_dispatcher = $event_dispatcher;
        $this->message_queue = $message_queue;
        $this->influx = $influx;

        $this->history_repo = $em->getRepository(Entity\SongHistory::class);
        $this->song_repo = $em->getRepository(Entity\Song::class);
        $this->listener_repo = $em->getRepository(Entity\Listener::class);

        /** @var Entity\Repository\SettingsRepository $settings_repo */
        $settings_repo = $em->getRepository(Entity\Settings::class);

        $this->settings_repo = $settings_repo;
        $this->analytics_level = $settings_repo->getSetting('analytics', Entity\Analytics::LEVEL_ALL);
    }

    public static function getSubscribedEvents()
    {
        if (APP_TESTING_MODE) {
            return [];
        }

        return [
            GenerateRawNowPlaying::class => [
                ['loadRawFromFrontend', 10],
                ['addToRawFromRemotes', 0],
                ['cleanUpRawOutput', -10],
            ],
        ];
    }

    public function run($force = false): void
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

        $this->cache->set('api_nowplaying_data', $nowplaying, 120);
        $this->settings_repo->setSetting('nowplaying', $nowplaying);
    }

    /**
     * @return Entity\Api\NowPlaying[]
     */
    protected function _loadNowPlaying($force = false): array
    {
        $stations = $this->em->getRepository(Entity\Station::class)
            ->findBy(['is_enabled' => 1]);

        $nowplaying = [];

        foreach ($stations as $station) {
            /** @var Entity\Station $station */
            $last_run = $station->getNowplayingTimestamp();

            if ($last_run >= (time()-10) && !$force) {
                $np = $station->getNowplaying();

                if ($np instanceof Entity\Api\NowPlaying) {
                    $np->update();
                    $nowplaying[] = $np;
                    continue;
                }
            }

            $nowplaying[] = $this->processStation($station);
        }

        return $nowplaying;
    }

    /**
     * Queue an individual station for processing its "Now Playing" metadata.
     *
     * @param Entity\Station $station
     * @param array $extra_metadata
     */
    public function queueStation(Entity\Station $station, array $extra_metadata = []): void
    {
        // Stop Now Playing from processing while doing the steps below.
        $station->setNowPlayingTimestamp(time());
        $this->em->persist($station);
        $this->em->flush($station);

        // Process extra metadata sent by Liquidsoap (if it exists).
        if (!empty($extra_metadata['song_id'])) {
            $song = $this->song_repo->find($extra_metadata['song_id']);

            if ($song instanceof Entity\Song) {
                $sh = $this->history_repo->getCuedSong($song, $station);
                if (!$sh instanceof Entity\SongHistory) {
                    $sh = new Entity\SongHistory($song, $station);
                    $sh->setTimestampCued(time());
                }

                if (!empty($extra_metadata['media_id']) && null === $sh->getMedia()) {
                    $media = $this->em->find(Entity\StationMedia::class, $extra_metadata['media_id']);
                    if ($media instanceof Entity\StationMedia) {
                        $sh->setMedia($media);
                    }
                }

                if (!empty($extra_metadata['playlist_id']) && null === $sh->getPlaylist()) {
                    $playlist = $this->em->find(Entity\StationPlaylist::class, $extra_metadata['playlist_id']);
                    if ($playlist instanceof Entity\StationPlaylist) {
                        $sh->setPlaylist($playlist);
                    }
                }

                $sh->sentToAutodj();

                $this->em->persist($sh);
                $this->em->flush($sh);
            }
        }

        // Trigger a delayed Now Playing update.
        $message = new Message\UpdateNowPlayingMessage;
        $message->station_id = $station->getId();
        $this->message_queue->produce($message);
    }

    /**
     * Handle event dispatch.
     *
     * @param Message\AbstractMessage $message
     */
    public function __invoke(Message\AbstractMessage $message)
    {
        try {
            if ($message instanceof Message\UpdateNowPlayingMessage) {
                $station = $this->em->find(Entity\Station::class, $message->station_id);

                if ($station instanceof Entity\Station) {
                    $this->processStation($station, true);
                }
            }
        } finally {
            $this->em->clear();
        }
    }

    /**
     * Generate Structured NowPlaying Data for a given station.
     *
     * @param Entity\Station $station
     * @param bool $standalone Whether the request is for this station alone or part of the regular sync process.
     * @return Entity\Api\NowPlaying
     */
    public function processStation(
        Entity\Station $station,
        $standalone = false): Entity\Api\NowPlaying
    {
        $this->logger->pushProcessor(function($record) use ($station) {
            $record['extra']['station'] = [
                'id' => $station->getId(),
                'name' => $station->getName(),
            ];
            return $record;
        });

        $include_clients = ($this->analytics_level === Entity\Analytics::LEVEL_ALL);

        $frontend_adapter = $this->adapters->getFrontendAdapter($station);
        $remote_adapters = $this->adapters->getRemoteAdapters($station);

        /** @var Entity\Api\NowPlaying|null $np_old */
        $np_old = $station->getNowplaying();

        // Build the new "raw" NowPlaying data.
        try {
            $event = new GenerateRawNowPlaying($station, $frontend_adapter, $remote_adapters, null, $include_clients);
            $this->event_dispatcher->dispatch($event);
            $np_raw = $event->getRawResponse();
        } catch(\Exception $e) {
            $this->logger->log(Logger::ERROR, $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'code' => $e->getCode(),
            ]);

            $np_raw = AdapterAbstract::NOWPLAYING_EMPTY;
        }

        $np = new Entity\Api\NowPlaying;
        $uri_empty = new Uri('');

        $np->station = $station->api($frontend_adapter, $remote_adapters, $uri_empty);
        $np->listeners = new Entity\Api\NowPlayingListeners($np_raw['listeners']);

        if (empty($np_raw['current_song']['text'])) {
            $song_obj = $this->song_repo->getOrCreate(['text' => 'Stream Offline'], true);

            $offline_sh = new Entity\Api\NowPlayingCurrentSong;
            $offline_sh->sh_id = 0;
            $offline_sh->song = $song_obj->api($this->api_utils, $uri_empty);
            $np->now_playing = $offline_sh;

            $np->song_history = $this->history_repo->getHistoryForStation($station, $this->api_utils, $uri_empty);
            $next_song = $this->autodj->getNextSong($station);
            if ($next_song instanceof Entity\SongHistory) {
                $np->playing_next = $next_song->api(new Entity\Api\SongHistory, $this->api_utils, $uri_empty);
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

                $np->song_history = $this->history_repo->getHistoryForStation($station, $this->api_utils, $uri_empty);

                $next_song = $this->autodj->getNextSong($station);
                if ($next_song instanceof Entity\SongHistory && $next_song->showInApis()) {
                    $np->playing_next = $next_song->api(new Entity\Api\SongHistory, $this->api_utils, $uri_empty);
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
            $np->now_playing = $sh_obj->api(new Entity\Api\NowPlayingCurrentSong, $this->api_utils, $uri_empty);
        }

        $np->update();

        $station->setNowplaying($np);

        $this->em->persist($station);
        $this->em->flush();

        // Trigger the dispatching of webhooks.

        /** @var Entity\Api\NowPlaying $np_event */
        $np_event = deep_copy($np);
        $np_event->resolveUrls($this->api_utils->getRouter()->getBaseUrl(false));
        $np_event->cache = 'event';

        $webhook_event = new SendWebhooks($station, $np_event, $np_old, $standalone);
        $this->event_dispatcher->dispatch($webhook_event);

        $this->logger->popProcessor();

        return $np;
    }

    public function loadRawFromFrontend(GenerateRawNowPlaying $event)
    {
        $np_raw = $event
            ->getFrontend()
            ->getNowPlaying($event->getStation(), $event->getPayload(), $event->includeClients());

        $event->setRawResponse($np_raw);
    }

    public function addToRawFromRemotes(GenerateRawNowPlaying $event)
    {
        $np_raw = $event->getRawResponse();

        // Loop through all remotes and update NP data accordingly.
        foreach($event->getRemotes() as $ra_proxy) {
            $np_raw = $ra_proxy->getAdapter()->updateNowPlaying($ra_proxy->getRemote(), $np_raw, $event->includeClients());
        }

        $event->setRawResponse($np_raw);
    }

    public function cleanUpRawOutput(GenerateRawNowPlaying $event)
    {
        $np_raw = $event->getRawResponse();

        array_walk($np_raw['current_song'], function(&$value) {
            $value = htmlspecialchars_decode($value);
            $value = trim($value);
        });

        $event->setRawResponse($np_raw);
    }
}
