<?php

declare(strict_types=1);

namespace App\Sync\NowPlaying\Task;

use App\Cache\NowPlayingCache;
use App\Container\EntityManagerAwareTrait;
use App\Container\LoggerAwareTrait;
use App\Container\SettingsAwareTrait;
use App\Entity\Api\NowPlaying\NowPlaying;
use App\Entity\ApiGenerator\NowPlayingApiGenerator;
use App\Entity\Repository\ListenerRepository;
use App\Entity\Station;
use App\Environment;
use App\Event\Radio\GenerateRawNowPlaying;
use App\Http\RouterInterface;
use App\Message;
use App\Nginx\HlsListeners;
use App\Radio\Adapters;
use App\Webhook\Enums\WebhookTriggers;
use DeepCopy\DeepCopy;
use Exception;
use GuzzleHttp\Promise\Utils;
use NowPlaying\Result\Result;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBus;

final class NowPlayingTask implements NowPlayingTaskInterface, EventSubscriberInterface
{
    use LoggerAwareTrait;
    use EntityManagerAwareTrait;
    use SettingsAwareTrait;

    public function __construct(
        private readonly Adapters $adapters,
        private readonly NowPlayingCache $nowPlayingCache,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly MessageBus $messageBus,
        private readonly RouterInterface $router,
        private readonly ListenerRepository $listenerRepo,
        private readonly NowPlayingApiGenerator $nowPlayingApiGenerator,
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

        $includeClients = $this->settingsRepo->readSettings()->isAnalyticsEnabled();

        // Build the new "raw" NowPlaying data.
        try {
            $event = new GenerateRawNowPlaying(
                $this->adapters,
                $station,
                $includeClients
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
        if ($includeClients && null !== $npResult->clients) {
            $this->listenerRepo->update($station, $npResult->clients);
        }

        $npOld = $this->nowPlayingCache->getForStation($station);

        $np = $this->nowPlayingApiGenerator->__invoke(
            $station,
            $npResult,
            $npOld
        );

        // Update caches
        $this->nowPlayingCache->setForStation($station, $np);

        // Trigger the dispatching of webhooks.
        $this->dispatchWebhooks($station, $np, $npOld);

        // Handle any entity changes persisted during NP update.
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
        Station $station,
        NowPlaying $npOriginal,
        ?NowPlaying $npOld
    ): void {
        /** @var NowPlaying $np */
        $np = (new DeepCopy())->copy($npOriginal);
        $np->resolveUrls($this->router->getBaseUrl());
        $np->cache = 'event';

        $triggers = [];

        if ($npOld instanceof NowPlaying) {
            if ($npOld->listeners->current > $np->listeners->current) {
                $triggers[] = WebhookTriggers::ListenerLost->value;
            } elseif ($npOld->listeners->current < $np->listeners->current) {
                $triggers[] = WebhookTriggers::ListenerGained->value;
            }

            if (!$npOld->live->is_live && $np->live->is_live) {
                $triggers[] = WebhookTriggers::LiveConnect->value;
            } elseif ($npOld->live->is_live && !$np->live->is_live) {
                $triggers[] = WebhookTriggers::LiveDisconnect->value;
            }

            if ($npOld->is_online && !$np->is_online) {
                $triggers[] = WebhookTriggers::StationOffline->value;
            } elseif (!$npOld->is_online && $np->is_online) {
                $triggers[] = WebhookTriggers::StationOnline->value;
            }

            if ($npOld->now_playing?->song?->id !== $np->now_playing?->song?->id) {
                $triggers[] = WebhookTriggers::SongChanged->value;

                if ($np->live->is_live) {
                    $triggers[] = WebhookTriggers::SongChangedLive->value;
                }
            }
        }

        $message = new Message\DispatchWebhookMessage();
        $message->station_id = $station->getIdRequired();
        $message->np = $np;
        $message->triggers = $triggers;

        $this->messageBus->dispatch($message);
    }
}
