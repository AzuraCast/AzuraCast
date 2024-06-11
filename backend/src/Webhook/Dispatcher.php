<?php

declare(strict_types=1);

namespace App\Webhook;

use App\Container\ContainerAwareTrait;
use App\Container\EntityManagerAwareTrait;
use App\Container\EnvironmentAwareTrait;
use App\Container\LoggerAwareTrait;
use App\Entity\ApiGenerator\NowPlayingApiGenerator;
use App\Entity\Station;
use App\Entity\StationWebhook;
use App\Http\RouterInterface;
use App\Message;
use App\Webhook\Connector\AbstractConnector;
use App\Webhook\Enums\WebhookTriggers;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use RuntimeException;
use Throwable;

final class Dispatcher
{
    use LoggerAwareTrait;
    use ContainerAwareTrait;
    use EntityManagerAwareTrait;
    use EnvironmentAwareTrait;

    public function __construct(
        private readonly RouterInterface $router,
        private readonly LocalWebhookHandler $localHandler,
        private readonly NowPlayingApiGenerator $nowPlayingApiGen
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
            $this->handleDispatch($message);
        } elseif ($message instanceof Message\TestWebhookMessage) {
            $this->testDispatch($message);
        }
    }

    private function handleDispatch(Message\DispatchWebhookMessage $message): void
    {
        $station = $this->em->find(Station::class, $message->station_id);
        if (!$station instanceof Station) {
            return;
        }

        $np = $message->np;
        $triggers = $message->triggers;

        // Always dispatch the special "local" updater task.
        try {
            $this->localHandler->dispatch($station, $np, $triggers);
        } catch (Throwable $e) {
            $this->logger->error(
                sprintf('%s L%d: %s', $e->getFile(), $e->getLine(), $e->getMessage()),
                [
                    'exception' => $e,
                ]
            );
        }

        if ($this->environment->isTesting()) {
            $this->logger->notice('In testing mode; no webhooks dispatched.');
            return;
        }

        /** @var StationWebhook[] $enabledWebhooks */
        $enabledWebhooks = $station->getWebhooks()->filter(
            function (StationWebhook $webhook) {
                return $webhook->getIsEnabled();
            }
        );

        $this->logger->debug('Webhook dispatch: triggering events: ' . implode(', ', $triggers));

        foreach ($enabledWebhooks as $webhook) {
            $webhookType = $webhook->getType();
            $webhookClass = $webhookType->getClass();

            if (null === $webhookClass) {
                $this->logger->error(
                    sprintf(
                        'Webhook type "%s" is no longer supported. Removing this webhook is recommended.',
                        $webhookType->value
                    )
                );
                continue;
            }

            $this->logger->debug(
                sprintf('Dispatching connector "%s".', $webhookType->value)
            );

            if (!$this->di->has($webhookClass)) {
                $this->logger->error(
                    sprintf('Webhook class "%s" not found.', $webhookClass)
                );
                continue;
            }

            /** @var AbstractConnector $connectorObj */
            $connectorObj = $this->di->get($webhookClass);

            if ($connectorObj->shouldDispatch($webhook, $triggers)) {
                try {
                    $connectorObj->dispatch($station, $webhook, $np, $triggers);
                    $webhook->updateLastSentTimestamp();
                    $this->em->persist($webhook);
                } catch (Throwable $e) {
                    $this->logger->error(
                        sprintf('%s L%d: %s', $e->getFile(), $e->getLine(), $e->getMessage()),
                        [
                            'exception' => $e,
                        ]
                    );
                }
            }
        }

        $this->em->flush();
    }

    private function testDispatch(
        Message\TestWebhookMessage $message
    ): void {
        $outputPath = $message->outputPath;

        if (null !== $outputPath) {
            $logHandler = new StreamHandler(
                $outputPath,
                Level::fromValue($message->logLevel),
                true
            );
            $this->logger->pushHandler($logHandler);
        }

        try {
            $webhook = $this->em->find(StationWebhook::class, $message->webhookId);
            if (!($webhook instanceof StationWebhook)) {
                $this->logger->error(
                    sprintf('Webhook ID %d not found.', $message->webhookId),
                );
                return;
            }

            $this->logger->info(sprintf('Dispatching test web hook "%s"...', $webhook->getName()));

            $station = $webhook->getStation();
            $np = $this->nowPlayingApiGen->currentOrEmpty($station);
            $np->resolveUrls($this->router->getBaseUrl());
            $np->cache = 'event';

            $this->localHandler->dispatch($station, $np, []);

            $webhookType = $webhook->getType();
            $webhookClass = $webhookType->getClass();

            if (null === $webhookClass) {
                throw new RuntimeException(
                    'Webhook type is no longer supported. Removing this webhook is recommended.'
                );
            }

            /** @var AbstractConnector $webhookObj */
            $webhookObj = $this->di->get($webhookClass);
            $webhookObj->dispatch($station, $webhook, $np, [
                WebhookTriggers::SongChanged->value,
            ]);

            $this->logger->info(sprintf('Web hook "%s" completed.', $webhook->getName()));
        } catch (Throwable $e) {
            $this->logger->error(
                sprintf(
                    '%s L%d: %s',
                    $e->getFile(),
                    $e->getLine(),
                    $e->getMessage()
                ),
                [
                    'exception' => $e,
                ]
            );
        } finally {
            if (null !== $outputPath) {
                $this->logger->popHandler();
            }
        }
    }
}
