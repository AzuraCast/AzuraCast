<?php

declare(strict_types=1);

namespace App\Webhook;

use App\Entity;
use App\Environment;
use App\Exception;
use App\Http\RouterInterface;
use App\Message;
use Doctrine\ORM\EntityManagerInterface;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Psr\Log\LogLevel;

final class Dispatcher
{
    public function __construct(
        private readonly Environment $environment,
        private readonly Logger $logger,
        private readonly EntityManagerInterface $em,
        private readonly RouterInterface $router,
        private readonly LocalWebhookHandler $localHandler,
        private readonly ConnectorLocator $connectors,
        private readonly Entity\ApiGenerator\NowPlayingApiGenerator $nowPlayingApiGen
    ) {
    }

    /**
     * Handle event dispatch.
     *
     * @param Message\AbstractMessage $message
     */
    public function __invoke(Message\AbstractMessage $message): void
    {
        if ($message instanceof Message\DispatchWebhookMessage) {
            $station = $this->em->find(Entity\Station::class, $message->station_id);
            if (!$station instanceof Entity\Station) {
                return;
            }

            $np = $message->np;
            $triggers = (array)$message->triggers;

            // Always dispatch the special "local" updater task.
            $this->localHandler->dispatch($station, $np);

            if ($this->environment->isTesting()) {
                $this->logger->notice('In testing mode; no webhooks dispatched.');
                return;
            }

            /** @var Entity\StationWebhook[] $enabledWebhooks */
            $enabledWebhooks = $station->getWebhooks()->filter(
                function (Entity\StationWebhook $webhook) {
                    return $webhook->getIsEnabled();
                }
            );

            $this->logger->debug('Webhook dispatch: triggering events: ' . implode(', ', $triggers));

            foreach ($enabledWebhooks as $webhook) {
                $connectorObj = $this->connectors->getConnector($webhook->getType());

                if ($connectorObj->shouldDispatch($webhook, $triggers)) {
                    $this->logger->debug(sprintf('Dispatching connector "%s".', $webhook->getType()));

                    if ($connectorObj->dispatch($station, $webhook, $np, $triggers)) {
                        $webhook->updateLastSentTimestamp();
                        $this->em->persist($webhook);
                        $this->em->flush();
                    }
                }
            }
        } elseif ($message instanceof Message\TestWebhookMessage) {
            $outputPath = $message->outputPath;

            if (null !== $outputPath) {
                $logHandler = new StreamHandler($outputPath, LogLevel::DEBUG, true);
                $this->logger->pushHandler($logHandler);
            }

            $webhook = $this->em->find(Entity\StationWebhook::class, $message->webhookId);
            if ($webhook instanceof Entity\StationWebhook) {
                $this->testDispatch($webhook);
            }

            if (null !== $outputPath) {
                $this->logger->popHandler();
            }
        }
    }

    /**
     * Send a "test" dispatch of the web hook, regardless of whether it is currently enabled, and
     * return any logging information this yields.
     *
     * @param Entity\StationWebhook $webhook
     *
     * @throws Exception
     */
    public function testDispatch(
        Entity\StationWebhook $webhook
    ): TestHandler {
        $station = $webhook->getStation();

        $handler = new TestHandler(LogLevel::DEBUG, true);
        $this->logger->pushHandler($handler);

        $np = $this->nowPlayingApiGen->currentOrEmpty($station);
        $np->resolveUrls($this->router->getBaseUrl());
        $np->cache = 'event';

        $connectorObj = $this->connectors->getConnector($webhook->getType());
        $connectorObj->dispatch($station, $webhook, $np, [Entity\StationWebhook::TRIGGER_ALL]);

        $this->logger->popHandler();

        return $handler;
    }
}
