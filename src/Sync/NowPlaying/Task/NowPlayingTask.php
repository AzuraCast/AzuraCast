<?php

declare(strict_types=1);

namespace App\Sync\NowPlaying\Task;

use App\Doctrine\ReloadableEntityManagerInterface;
use App\Entity;
use App\Entity\Api\NowPlaying\NowPlaying;
use App\Entity\Station;
use App\Environment;
use App\Event\Radio\GenerateRawNowPlaying;
use App\Http\RouterInterface;
use App\Message;
use App\Radio\Adapters;
use DeepCopy\DeepCopy;
use Exception;
use Monolog\Logger;
use NowPlaying\Result\Result;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBus;

class NowPlayingTask implements NowPlayingTaskInterface, EventSubscriberInterface
{
    public function __construct(
        protected Adapters $adapters,
        protected CacheInterface $cache,
        protected EventDispatcherInterface $eventDispatcher,
        protected MessageBus $messageBus,
        protected RouterInterface $router,
        protected Entity\Repository\ListenerRepository $listenerRepo,
        protected Entity\Repository\SettingsRepository $settingsRepo,
        protected Entity\ApiGenerator\NowPlayingApiGenerator $nowPlayingApiGenerator,
        protected ReloadableEntityManagerInterface $em,
        protected LoggerInterface $logger,
    ) {
    }

    /**
     * @inheritDoc
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

    public function run(Station $station): void
    {
        if (!$station->getIsEnabled()) {
            return;
        }

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
                'id'   => $station->getId(),
                'name' => $station->getName(),
                'np'   => $npResult,
            ]
        );

        // Update detailed listener statistics, if they exist for the station
        if ($include_clients && null !== $npResult->clients) {
            $this->listenerRepo->update($station, $npResult->clients);
        }

        $np = ($this->nowPlayingApiGenerator)($station, $npResult);

        // Trigger the dispatching of webhooks.
        $this->dispatchWebhooks($station, $np);

        // Update caches
        $this->cache->set('nowplaying.' . $station->getIdRequired(), $np, 120);
        $this->cache->set('nowplaying.' . $station->getShortName(), $np, 120);

        $station->setNowplaying($np);
        $this->em->persist($station);
        $this->em->flush();
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
        NowPlaying $npOriginal
    ): void {
        /** @var NowPlaying $np */
        $np = (new DeepCopy())->copy($npOriginal);
        $np->resolveUrls($this->router->getBaseUrl());
        $np->cache = 'event';

        $npOld = $station->getNowplaying();
        $triggers = [
            Entity\StationWebhook::TRIGGER_ALL,
        ];

        if ($npOld instanceof NowPlaying) {
            if ($npOld->now_playing?->song?->id !== $np->now_playing?->song?->id) {
                $triggers[] = Entity\StationWebhook::TRIGGER_SONG_CHANGED;
            }

            if ($npOld->listeners->current > $np->listeners->current) {
                $triggers[] = Entity\StationWebhook::TRIGGER_LISTENER_LOST;
            } elseif ($npOld->listeners->current < $np->listeners->current) {
                $triggers[] = Entity\StationWebhook::TRIGGER_LISTENER_GAINED;
            }

            if (!$npOld->live->is_live && $np->live->is_live) {
                $triggers[] = Entity\StationWebhook::TRIGGER_LIVE_CONNECT;
            } elseif ($npOld->live->is_live && !$np->live->is_live) {
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
}
