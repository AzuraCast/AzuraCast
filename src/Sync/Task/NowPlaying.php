<?php

namespace App\Sync\Task;

use App\Entity;
use App\Entity\Station;
use App\Event\Radio\GenerateRawNowPlaying;
use App\Event\SendWebhooks;
use App\EventDispatcher;
use App\Http\RouterInterface;
use App\LockFactory;
use App\Message;
use App\Radio\Adapters;
use App\Radio\AutoDJ;
use App\Settings;
use DeepCopy\DeepCopy;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Monolog\Logger;
use NowPlaying\Result\Result;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Lock\LockInterface;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\Stamp\DelayStamp;

class NowPlaying extends AbstractTask implements EventSubscriberInterface
{
    protected CacheInterface $cache;

    protected Adapters $adapters;

    protected AutoDJ $autodj;

    protected EventDispatcher $eventDispatcher;

    protected MessageBus $messageBus;

    protected Entity\Repository\StationQueueRepository $queueRepo;

    protected Entity\Repository\ListenerRepository $listenerRepo;

    protected LockFactory $lockFactory;

    protected Entity\ApiGenerator\NowPlayingApiGenerator $nowPlayingApiGenerator;

    protected RouterInterface $router;

    protected string $analyticsLevel = Entity\Analytics::LEVEL_ALL;

    public function __construct(
        EntityManagerInterface $em,
        Entity\Repository\SettingsRepository $settingsRepository,
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
        Entity\ApiGenerator\NowPlayingApiGenerator $nowPlayingApiGenerator
    ) {
        parent::__construct($em, $settingsRepository, $logger);

        $this->adapters = $adapters;
        $this->autodj = $autodj;
        $this->cache = $cache;
        $this->eventDispatcher = $event_dispatcher;
        $this->messageBus = $messageBus;
        $this->lockFactory = $lockFactory;
        $this->router = $router;

        $this->listenerRepo = $listenerRepository;
        $this->queueRepo = $queueRepo;

        $this->nowPlayingApiGenerator = $nowPlayingApiGenerator;

        $this->analyticsLevel = (string)$settingsRepository->getSetting('analytics', Entity\Analytics::LEVEL_ALL);
    }

    /**
     * @return mixed[]
     */
    public static function getSubscribedEvents(): array
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
        $nowplaying = $this->loadNowPlaying($force);

        $this->cache->set(Entity\Settings::NOWPLAYING, $nowplaying, 120);
        $this->settingsRepo->setSetting(Entity\Settings::NOWPLAYING, $nowplaying);
    }

    /**
     * @param bool $force
     *
     * @return Entity\Api\NowPlaying[]
     */
    protected function loadNowPlaying($force = false): array
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

            $include_clients = ($this->analyticsLevel === Entity\Analytics::LEVEL_ALL);

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

            // Update detailed listener statistics, if they exist for the station
            if ($include_clients && null !== $npResult->clients) {
                $this->listenerRepo->update($station, $npResult->clients);
            }

            $np = ($this->nowPlayingApiGenerator)($station, $npResult);

            // Trigger the dispatching of webhooks.

            /** @var Entity\Api\NowPlaying $np_event */
            $np_event = (new DeepCopy())->copy($np);
            $np_event->resolveUrls($this->router->getBaseUrl(false));
            $np_event->cache = 'event';

            $webhook_event = new SendWebhooks($station, $np_event, $standalone);

            $this->eventDispatcher->dispatch($webhook_event);

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

    protected function getLockForStation(Station $station): LockInterface
    {
        return $this->lockFactory->createLock('nowplaying_station_' . $station->getId(), 600);
    }
}
