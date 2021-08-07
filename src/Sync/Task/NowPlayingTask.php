<?php

declare(strict_types=1);

namespace App\Sync\Task;

use App\Doctrine\ReloadableEntityManagerInterface;
use App\Entity;
use App\Environment;
use App\Event\Radio\GenerateRawNowPlaying;
use App\Http\RouterInterface;
use App\LockFactory;
use App\Message;
use App\Radio\Adapters;
use App\Radio\AutoDJ;
use DeepCopy\DeepCopy;
use Exception;
use Monolog\Logger;
use NowPlaying\Result\Result;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\Stamp\DelayStamp;

class NowPlayingTask extends AbstractTask implements EventSubscriberInterface
{
    public function __construct(
        protected Adapters $adapters,
        protected AutoDJ $autodj,
        protected CacheInterface $cache,
        protected EventDispatcherInterface $eventDispatcher,
        protected MessageBus $messageBus,
        protected LockFactory $lockFactory,
        protected RouterInterface $router,
        protected Entity\Repository\ListenerRepository $listenerRepo,
        protected Entity\Repository\StationQueueRepository $queueRepo,
        protected Entity\Repository\SettingsRepository $settingsRepo,
        protected Entity\ApiGenerator\NowPlayingApiGenerator $nowPlayingApiGenerator,
        ReloadableEntityManagerInterface $em,
        LoggerInterface $logger,
    ) {
        parent::__construct($em, $logger);
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

        $settings = $this->settingsRepo->readSettings();
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
        $nowplaying = [];

        foreach ($this->iterateStations() as $station) {
            if (!$station->isEnabled()) {
                continue;
            }

            $nowplaying[] = $this->processStation(
                station: $station,
                force: $force
            );
        }

        return $nowplaying;
    }

    public function processStation(
        Entity\Station $station,
        bool $standalone = false,
        bool $force = false
    ): Entity\Api\NowPlaying {
        $lock = $this->lockFactory->createAndAcquireLock(
            resource: 'nowplaying_station_' . $station->getId(),
            ttl: 600,
            force: $force
        );

        if (false === $lock) {
            return $this->nowPlayingApiGenerator->currentOrEmpty($station);
        }

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
            $this->dispatchWebhooks($station, $np);

            if ($standalone) {
                $this->updateCaches($station, $np);
            }

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
        // Process extra metadata sent by Liquidsoap (if it exists).
        if (!empty($extra_metadata['media_id'])) {
            $media = $this->em->find(Entity\StationMedia::class, $extra_metadata['media_id']);
            if (!$media instanceof Entity\StationMedia) {
                return;
            }

            $sq = $this->queueRepo->findRecentlyCuedSong($station, $media);

            if (!$sq instanceof Entity\StationQueue) {
                $sq = Entity\StationQueue::fromMedia($station, $media);
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
        $message->station_id = $station->getIdRequired();

        $this->messageBus->dispatch(
            $message,
            [
                new DelayStamp(2000),
            ]
        );
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
                $this->processStation(
                    station: $station,
                    standalone: true
                );
            }
        }
    }

    public function loadRawFromFrontend(GenerateRawNowPlaying $event): void
    {
        try {
            $result = $event
                ->getFrontend()
                ->getNowPlaying($event->getStation(), $event->includeClients());
        } catch (Exception $e) {
            $this->logger->error(sprintf('NowPlaying adapter error: %s', $e->getMessage()));
            return;
        }

        $event->setResult($result);
    }

    public function addToRawFromRemotes(GenerateRawNowPlaying $event): void
    {
        $result = $event->getResult();

        // Loop through all remotes and update NP data accordingly.
        foreach ($event->getRemotes() as $ra_proxy) {
            try {
                $result = $ra_proxy->getAdapter()->updateNowPlaying(
                    $result,
                    $ra_proxy->getRemote(),
                    $event->includeClients()
                );
            } catch (Exception $e) {
                $this->logger->error(sprintf('NowPlaying adapter error: %s', $e->getMessage()));
            }
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
        $np->resolveUrls($this->router->getBaseUrl());
        $np->cache = 'event';

        $npOld = $station->getNowplaying();
        $triggers = [
            Entity\StationWebhook::TRIGGER_ALL,
        ];

        if ($npOld instanceof Entity\Api\NowPlaying) {
            if ($npOld->now_playing?->song?->id !== $np->now_playing?->song?->id) {
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

        $this->messageBus->dispatch($message);
    }

    protected function updateCaches(
        Entity\Station $station,
        Entity\Api\NowPlaying $np
    ): void {
        // Replace the relevant station information in the cache and database.
        $this->logger->debug('Updating NowPlaying cache...');

        $np_full = $this->cache->get('nowplaying');

        if ($np_full) {
            $np_new = [];
            foreach ($np_full as $np_old) {
                /** @var Entity\Api\NowPlaying $np_old */
                if ($np_old->station->id === $station->getId()) {
                    $np_new[] = $np;
                } else {
                    $np_new[] = $np_old;
                }
            }

            $this->cache->set('nowplaying', $np_new, 120);

            $settings = $this->settingsRepo->readSettings();
            $settings->setNowplaying($np_new);
            $this->settingsRepo->writeSettings($settings);
        }
    }
}
