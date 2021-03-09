<?php

namespace App\Sync\Task;

use App\Doctrine\ReloadableEntityManagerInterface;
use App\Entity;
use App\Environment;
use App\Event\Radio\GenerateRawNowPlaying;
use App\Event\SendWebhooks;
use App\EventDispatcher;
use App\Http\RouterInterface;
use App\LockFactory;
use App\Message;
use App\Radio\Adapters;
use App\Radio\AutoDJ;
use DeepCopy\DeepCopy;
use Exception;
use Monolog\Logger;
use NowPlaying\Result\Result;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Lock\LockInterface;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\Stamp\DelayStamp;

class NowPlayingTask extends AbstractTask implements EventSubscriberInterface
{
    protected CacheInterface $cache;

    protected Adapters $adapters;

    protected AutoDJ $autodj;

    protected EventDispatcher $eventDispatcher;

    protected MessageBus $messageBus;

    protected Entity\Repository\StationQueueRepository $queueRepo;

    protected Entity\Repository\ListenerRepository $listenerRepo;

    protected Entity\Repository\SettingsRepository $settingsRepo;

    protected LockFactory $lockFactory;

    protected Entity\ApiGenerator\NowPlayingApiGenerator $nowPlayingApiGenerator;

    protected RouterInterface $router;

    public function __construct(
        ReloadableEntityManagerInterface $em,
        LoggerInterface $logger,
        Adapters $adapters,
        AutoDJ $autodj,
        CacheInterface $cache,
        EventDispatcher $event_dispatcher,
        MessageBus $messageBus,
        LockFactory $lockFactory,
        RouterInterface $router,
        Entity\Repository\ListenerRepository $listenerRepository,
        Entity\Repository\StationQueueRepository $queueRepo,
        Entity\Repository\SettingsRepository $settingsRepo,
        Entity\ApiGenerator\NowPlayingApiGenerator $nowPlayingApiGenerator
    ) {
        parent::__construct($em, $logger);

        $this->adapters = $adapters;
        $this->autodj = $autodj;
        $this->cache = $cache;
        $this->eventDispatcher = $event_dispatcher;
        $this->messageBus = $messageBus;
        $this->lockFactory = $lockFactory;
        $this->router = $router;

        $this->listenerRepo = $listenerRepository;
        $this->queueRepo = $queueRepo;
        $this->settingsRepo = $settingsRepo;

        $this->nowPlayingApiGenerator = $nowPlayingApiGenerator;
    }

    /**
     * @return mixed[]
     */
    public static function getSubscribedEvents(): array
    {
        if (Environment::getInstance()->isTesting()) {
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
        $nowplaying = $this->loadNowPlaying($force);

        $this->cache->set('nowplaying', $nowplaying, 120);

        $settings = $this->settingsRepo->readSettings(true);
        $settings->setNowplaying($nowplaying);
        $this->settingsRepo->writeSettings($settings);
    }

    /**
     * @param bool $force
     *
     * @return Entity\Api\NowPlaying[]
     */
    protected function loadNowPlaying(bool $force = false): array
    {
        $stations = $this->em->getRepository(Entity\Station::class)
            ->findBy(['is_enabled' => 1]);

        $nowplaying = [];
        foreach ($stations as $station) {
            $nowplaying[] = $this->processStation($station);
        }

        return $nowplaying;
    }

    public function processStation(
        Entity\Station $station,
        bool $standalone = false
    ): Entity\Api\NowPlaying {
        $lock = $this->getLockForStation($station);
        $lock->acquire(true);

        try {
            /** @var Logger $logger */
            $logger = $this->logger;

            $logger->pushProcessor(
                function ($record) use ($station) {
                    $record['extra']['station'] = [
                        'id' => $station->getId(),
                        'name' => $station->getName(),
                    ];
                    return $record;
                }
            );

            $settings = $this->settingsRepo->readSettings();
            $include_clients = (Entity\Analytics::LEVEL_NONE !== $settings->getAnalytics());

            $frontend_adapter = $this->adapters->getFrontendAdapter($station);
            $remote_adapters = $this->adapters->getRemoteAdapters($station);

            // Build the new "raw" NowPlaying data.
            try {
                $event = new GenerateRawNowPlaying(
                    $station,
                    $frontend_adapter,
                    $remote_adapters,
                    $include_clients
                );
                $this->eventDispatcher->dispatch($event);

                $npResult = $event->getResult();
            } catch (Exception $e) {
                $this->logger->log(
                    Logger::ERROR,
                    $e->getMessage(),
                    [
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'code' => $e->getCode(),
                    ]
                );

                $npResult = Result::blank();
            }

            $this->logger->debug(
                'Final NowPlaying Response for Station',
                [
                    'id' => $station->getId(),
                    'name' => $station->getName(),
                    'np' => $npResult,
                ]
            );

            // Update detailed listener statistics, if they exist for the station
            if ($include_clients && null !== $npResult->clients) {
                $this->listenerRepo->update($station, $npResult->clients);
            }

            $np = ($this->nowPlayingApiGenerator)($station, $npResult);

            // Trigger the dispatching of webhooks.
            $this->dispatchWebhooks($station, $np, $standalone);

            $station->setNowplaying($np);
            $this->em->persist($station);
            $this->em->flush();

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
            $message = new Message\UpdateNowPlayingMessage();
            $message->station_id = $station->getId();

            $this->messageBus->dispatch(
                $message,
                [
                    new DelayStamp(2000),
                ]
            );
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

    protected function dispatchWebhooks(
        Entity\Station $station,
        Entity\Api\NowPlaying $npOriginal,
        bool $isStandalone = true
    ): void {
        /** @var Entity\Api\NowPlaying $np */
        $np = (new DeepCopy())->copy($npOriginal);
        $np->resolveUrls($this->router->getBaseUrl(false));
        $np->cache = 'event';

        $npOld = $station->getNowplaying();
        $triggers = [
            Entity\StationWebhook::TRIGGER_ALL,
        ];

        if ($npOld instanceof Entity\Api\NowPlaying) {
            if ($npOld->now_playing->song->id !== $np->now_playing->song->id) {
                $triggers[] = Entity\StationWebhook::TRIGGER_SONG_CHANGED;
            }

            if ($npOld->listeners->current > $np->listeners->current) {
                $triggers[] = Entity\StationWebhook::TRIGGER_LISTENER_LOST;
            } elseif ($npOld->listeners->current < $np->listeners->current) {
                $triggers[] = Entity\StationWebhook::TRIGGER_LISTENER_GAINED;
            }

            if ($npOld->live->is_live === false && $np->live->is_live === true) {
                $triggers[] = Entity\StationWebhook::TRIGGER_LIVE_CONNECT;
            } elseif ($npOld->live->is_live === true && $np->live->is_live === false) {
                $triggers[] = Entity\StationWebhook::TRIGGER_LIVE_DISCONNECT;
            }

            if ($npOld->is_online && !$np->is_online) {
                $triggers[] = Entity\StationWebhook::TRIGGER_STATION_OFFLINE;
            } elseif (!$npOld->is_online && $np->is_online) {
                $triggers[] = Entity\StationWebhook::TRIGGER_STATION_ONLINE;
            }
        }

        $message = new Message\DispatchWebhookMessage();
        $message->station_id = (int)$station->getId();
        $message->np = $np;
        $message->triggers = $triggers;
        $message->is_standalone = $isStandalone;

        $this->messageBus->dispatch($message);
    }

    protected function getLockForStation(Entity\Station $station): LockInterface
    {
        return $this->lockFactory->createLock('nowplaying_station_' . $station->getId(), 600);
    }
}
