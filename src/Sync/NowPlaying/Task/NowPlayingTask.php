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
use App\Nginx\HlsListeners;
use App\Radio\Adapters;
use DeepCopy\DeepCopy;
use Exception;
use GuzzleHttp\Promise\Utils;
use NowPlaying\Result\Result;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBus;

final class NowPlayingTask implements NowPlayingTaskInterface, EventSubscriberInterface
{
    public function __construct(
        private readonly Adapters $adapters,
        private readonly CacheInterface $cache,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly MessageBus $messageBus,
        private readonly RouterInterface $router,
        private readonly Entity\Repository\ListenerRepository $listenerRepo,
        private readonly Entity\Repository\SettingsRepository $settingsRepo,
        private readonly Entity\ApiGenerator\NowPlayingApiGenerator $nowPlayingApiGenerator,
        private readonly ReloadableEntityManagerInterface $em,
        private readonly LoggerInterface $logger,
        private readonly HlsListeners $hlsListeners,
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
                ['addToRawFromHls', -10],
            ],
        ];
    }

    public function run(Station $station): void
    {
        if (!$station->getIsEnabled()) {
            return;
        }

        $include_clients = $this->settingsRepo->readSettings()->isAnalyticsEnabled();

        // Build the new "raw" NowPlaying data.
        try {
            $event = new GenerateRawNowPlaying(
                $this->adapters,
                $station,
                $include_clients
            );
            $this->eventDispatcher->dispatch($event);

            $npResult = $event->getResult();
        } catch (Exception $e) {
            $this->logger->error(
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
            $result = $event->getFrontend()?->getNowPlaying($event->getStation(), $event->includeClients());
            if (null !== $result) {
                $event->setResult($result);
            }
        } catch (Exception $e) {
            $this->logger->error(sprintf('NowPlaying adapter error: %s', $e->getMessage()));
        }
    }

    public function addToRawFromRemotes(GenerateRawNowPlaying $event): void
    {
        // Loop through all remotes and update NP data accordingly.
        $remotePromises = [];
        foreach ($event->getRemotes() as $remote) {
            $remotePromises[] = $event->getRemoteAdapter($remote)->getNowPlayingAsync(
                $remote,
                $event->includeClients()
            );
        }

        $remotePromiseResults = Utils::settle($remotePromises)->wait();

        $this->em->flush();

        $result = $event->getResult();
        foreach ($remotePromiseResults as $promiseResult) {
            $remoteNp = $promiseResult['value'] ?? null;
            if (null !== $remoteNp) {
                $result = $result->merge($remoteNp);
            }
        }
        $event->setResult($result);
    }

    public function addToRawFromHls(GenerateRawNowPlaying $event): void
    {
        try {
            $event->setResult(
                $this->hlsListeners->updateNowPlaying(
                    $event->getResult(),
                    $event->getStation(),
                    $event->includeClients()
                )
            );
        } catch (Exception $e) {
            $this->logger->error(sprintf('HLS error: %s', $e->getMessage()));
        }
    }

    private function dispatchWebhooks(
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
