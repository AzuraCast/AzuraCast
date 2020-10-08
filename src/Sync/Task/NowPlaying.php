<?php
namespace App\Sync\Task;

use App\ApiUtilities;
use App\Entity;
use App\Entity\Station;
use App\Event\Radio\GenerateRawNowPlaying;
use App\Event\SendWebhooks;
use App\EventDispatcher;
use App\Message;
use App\Radio\Adapters;
use App\Radio\AutoDJ;
use App\Settings;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use GuzzleHttp\Psr7\Uri;
use Monolog\Logger;
use NowPlaying\Result\Result;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\LockInterface;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use function DeepCopy\deep_copy;

class NowPlaying extends AbstractTask implements EventSubscriberInterface
{
    protected CacheInterface $cache;

    protected Adapters $adapters;

    protected AutoDJ $autodj;

    protected EventDispatcher $event_dispatcher;

    protected MessageBus $messageBus;

    protected ApiUtilities $api_utils;

    protected Entity\Repository\SongHistoryRepository $history_repo;

    protected Entity\Repository\StationQueueRepository $queueRepo;

    protected Entity\Repository\ListenerRepository $listener_repo;

    protected LockFactory $lockFactory;

    protected string $analytics_level = Entity\Analytics::LEVEL_ALL;

    public function __construct(
        EntityManagerInterface $em,
        Adapters $adapters,
        ApiUtilities $api_utils,
        AutoDJ $autodj,
        CacheInterface $cache,
        LoggerInterface $logger,
        EventDispatcher $event_dispatcher,
        MessageBus $messageBus,
        LockFactory $lockFactory,
        Entity\Repository\SongHistoryRepository $historyRepository,
        Entity\Repository\ListenerRepository $listenerRepository,
        Entity\Repository\SettingsRepository $settingsRepository,
        Entity\Repository\StationQueueRepository $queueRepo
    ) {
        parent::__construct($em, $settingsRepository, $logger);

        $this->adapters = $adapters;
        $this->api_utils = $api_utils;
        $this->autodj = $autodj;
        $this->cache = $cache;
        $this->event_dispatcher = $event_dispatcher;
        $this->messageBus = $messageBus;
        $this->lockFactory = $lockFactory;

        $this->history_repo = $historyRepository;
        $this->listener_repo = $listenerRepository;
        $this->queueRepo = $queueRepo;

        $this->analytics_level = $settingsRepository->getSetting('analytics', Entity\Analytics::LEVEL_ALL);
    }

    public static function getSubscribedEvents()
    {
        if (Settings::getInstance()->isTesting()) {
            return [];
        }

        return [
            GenerateRawNowPlaying::class => [
                ['loadRawFromFrontend', 10],
                ['addToRawFromRemotes', 0],
            ],
        ];
    }

    public function run(bool $force = false): void
    {
        $nowplaying = $this->_loadNowPlaying($force);

        $this->cache->set(Entity\Settings::NOWPLAYING, $nowplaying, 120);
        $this->settingsRepo->setSetting(Entity\Settings::NOWPLAYING, $nowplaying);
    }

    /**
     * @param bool $force
     *
     * @return Entity\Api\NowPlaying[]
     */
    protected function _loadNowPlaying($force = false): array
    {
        $stations = $this->em->getRepository(Entity\Station::class)
            ->findBy(['is_enabled' => 1]);

        $nowplaying = [];
        foreach ($stations as $station) {
            $nowplaying[] = $this->processStation($station);
        }

        return $nowplaying;
    }

    /**
     * Generate Structured NowPlaying Data for a given station.
     *
     * @param Entity\Station $station
     * @param bool $standalone Whether the request is for this station alone or part of the regular sync process.
     *
     * @return Entity\Api\NowPlaying
     */
    public function processStation(
        Entity\Station $station,
        $standalone = false
    ): Entity\Api\NowPlaying {
        $lock = $this->getLockForStation($station);
        $lock->acquire(true);

        try {
            /** @var Logger $logger */
            $logger = $this->logger;

            $logger->pushProcessor(function ($record) use ($station) {
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
                $event = new GenerateRawNowPlaying(
                    $station,
                    $frontend_adapter,
                    $remote_adapters,
                    $include_clients);
                $this->event_dispatcher->dispatch($event);

                $npResult = $event->getResult();
            } catch (Exception $e) {
                $this->logger->log(Logger::ERROR, $e->getMessage(), [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'code' => $e->getCode(),
                ]);

                $npResult = Result::blank();
            }

            $this->logger->debug('Final NowPlaying Response for Station', [
                'id' => $station->getId(),
                'name' => $station->getName(),
                'np' => $npResult,
            ]);

            $np = new Entity\Api\NowPlaying;
            $uri_empty = new Uri('');

            $np->station = $station->api($frontend_adapter, $remote_adapters, $uri_empty);
            $np->listeners = new Entity\Api\NowPlayingListeners([
                    'current' => $npResult->listeners->current,
                    'unique' => $npResult->listeners->unique,
                    'total' => $npResult->listeners->total,
                ]
            );

            if (empty($npResult->currentSong->text)) {
                $song_obj = Entity\Song::createFromText('Stream Offline');

                $offline_sh = new Entity\Api\NowPlayingCurrentSong;
                $offline_sh->sh_id = 0;
                $offline_sh->song = $song_obj->api(
                    $this->api_utils,
                    $station,
                    $uri_empty
                );
                $np->now_playing = $offline_sh;

                $np->song_history = $this->history_repo->getHistoryApi(
                    $station,
                    $this->api_utils,
                    $uri_empty
                );

                $np->playing_next = $this->queueRepo->getNextSongApi(
                    $station,
                    $this->api_utils,
                    $uri_empty
                );

                $np->live = new Entity\Api\NowPlayingLive(false);
            } else {
                // Pull from current NP data if song details haven't changed .
                $current_song_hash = Entity\Song::getSongHash($npResult->currentSong);

                if ($np_old instanceof Entity\Api\NowPlaying &&
                    0 === strcmp($current_song_hash, $np_old->now_playing->song->id)) {
                    $previousHistory = $this->history_repo->getCurrent($station)
                        ?? Entity\Song::createFromApiSong($np_old->now_playing->song);

                    $sh_obj = $this->history_repo->register($previousHistory, $station, $np);

                    $np->song_history = $np_old->song_history;
                    $np->playing_next = $np_old->playing_next;
                } else {
                    // SongHistory registration must ALWAYS come before the history/nextsong calls
                    // otherwise they will not have up-to-date database info!
                    $sh_obj = $this->history_repo->register(Entity\Song::createFromNowPlayingSong($npResult->currentSong),
                        $station, $np);

                    $np->song_history = $this->history_repo->getHistoryApi(
                        $station,
                        $this->api_utils,
                        $uri_empty
                    );

                    $np->playing_next = $this->queueRepo->getNextSongApi(
                        $station,
                        $this->api_utils,
                        $uri_empty
                    );
                }

                // Update detailed listener statistics, if they exist for the station
                if ($include_clients && null !== $npResult->clients) {
                    $this->listener_repo->update($station, $npResult->clients);
                }

                // Detect and report live DJ status
                if ($station->getIsStreamerLive()) {
                    $current_streamer = $station->getCurrentStreamer();
                    $streamer_name = ($current_streamer instanceof Entity\StationStreamer)
                        ? $current_streamer->getDisplayName()
                        : 'Live DJ';

                    $broadcast_start_time = null;
                    $broadcast = $this->getLatestBroadcast($station);
                    if (!empty($broadcast)) {
                        $broadcast_start_time = $broadcast->getTimestampStart();
                    }

                    $np->live = new Entity\Api\NowPlayingLive(true, $streamer_name, $broadcast_start_time);
                } else {
                    $np->live = new Entity\Api\NowPlayingLive(false, '');
                }

                // Register a new item in song history.
                $np->now_playing = $sh_obj->api(new Entity\Api\NowPlayingCurrentSong, $this->api_utils,
                    $uri_empty);
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

            $webhook_event = new SendWebhooks($station, $np_event, $standalone);
            $webhook_event->computeTriggers($np_old);

            $this->event_dispatcher->dispatch($webhook_event);

            $logger->popProcessor();

            return $np;
        } finally {
            $lock->release();
        }
    }

    /**
     * Queue an individual station for processing its "Now Playing" metadata.
     *
     * @param Entity\Station $station
     * @param array $extra_metadata
     */
    public function queueStation(Entity\Station $station, array $extra_metadata = []): void
    {
        $lock = $this->getLockForStation($station);

        if (!$lock->acquire(true)) {
            return;
        }

        try {
            // Process extra metadata sent by Liquidsoap (if it exists).
            if (!empty($extra_metadata['media_id'])) {
                $media = $this->em->find(Entity\StationMedia::class, $extra_metadata['media_id']);
                if (!$media instanceof Entity\StationMedia) {
                    return;
                }

                $sq = $this->queueRepo->getUpcomingFromSong($station, $media);

                if (!$sq instanceof Entity\StationQueue) {
                    $sq = new Entity\StationQueue($station, $media);
                    $sq->setTimestampCued(time());
                } elseif (null === $sq->getMedia()) {
                    $sq->setMedia($media);
                }

                if (!empty($extra_metadata['playlist_id']) && null === $sq->getPlaylist()) {
                    $playlist = $this->em->find(Entity\StationPlaylist::class, $extra_metadata['playlist_id']);
                    if ($playlist instanceof Entity\StationPlaylist) {
                        $sq->setPlaylist($playlist);
                    }
                }

                $sq->sentToAutodj();

                $this->em->persist($sq);
                $this->em->flush();
            }

            // Trigger a delayed Now Playing update.
            $message = new Message\UpdateNowPlayingMessage;
            $message->station_id = $station->getId();

            $this->messageBus->dispatch($message, [
                new DelayStamp(2000),
            ]);
        } finally {
            $lock->release();
        }
    }

    /**
     * Handle event dispatch.
     *
     * @param Message\AbstractMessage $message
     */
    public function __invoke(Message\AbstractMessage $message): void
    {
        if ($message instanceof Message\UpdateNowPlayingMessage) {
            $station = $this->em->find(Entity\Station::class, $message->station_id);

            if ($station instanceof Entity\Station) {
                $this->processStation($station, true);
            }
        }
    }

    public function loadRawFromFrontend(GenerateRawNowPlaying $event): void
    {
        $result = $event
            ->getFrontend()
            ->getNowPlaying($event->getStation(), $event->includeClients());

        $event->setResult($result);
    }

    public function addToRawFromRemotes(GenerateRawNowPlaying $event): void
    {
        $result = $event->getResult();

        // Loop through all remotes and update NP data accordingly.
        foreach ($event->getRemotes() as $ra_proxy) {
            $result = $ra_proxy->getAdapter()->updateNowPlaying(
                $result,
                $ra_proxy->getRemote(),
                $event->includeClients()
            );
        }

        $event->setResult($result);
    }

    /**
     * Returns the latest live broadcast
     *
     * @param Entity\Station $station
     *
     * @return Entity\StationStreamerBroadcast
     */
    public function getLatestBroadcast(Station $station): Entity\StationStreamerBroadcast
    {
        $query = $this->em->createQuery(/** @lang DQL */ 'SELECT ssb 
            FROM App\Entity\StationStreamerBroadcast ssb
            WHERE ssb.station = :station AND ssb.streamer = :streamer
            ORDER BY ssb.timestampStart DESC
            ')
            ->setMaxResults(1)
            ->setParameter('station', $station)
            ->setParameter('streamer', $station->getCurrentStreamer());

        return $query->getSingleResult();
    }

    protected function getLockForStation(Station $station): LockInterface
    {
        return $this->lockFactory->createLock('nowplaying_station_' . $station->getId(), 600);
    }
}
