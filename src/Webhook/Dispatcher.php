<?php

namespace App\Webhook;

use App\Entity;
use App\Event\SendWebhooks;
use App\Exception;
use App\Http\RouterInterface;
use App\Message;
use App\Settings;
use Doctrine\ORM\EntityManagerInterface;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBus;

class Dispatcher implements EventSubscriberInterface
{
    protected Logger $logger;

    protected MessageBus $messageBus;

    protected LocalWebhookHandler $localHandler;

    protected ConnectorLocator $connectors;

    protected RouterInterface $router;

    protected EntityManagerInterface $em;

    public function __construct(
        Logger $logger,
        EntityManagerInterface $em,
        MessageBus $messageBus,
        RouterInterface $router,
        LocalWebhookHandler $localHandler,
        ConnectorLocator $connectors
    ) {
        $this->logger = $logger;
        $this->em = $em;
        $this->messageBus = $messageBus;
        $this->router = $router;
        $this->localHandler = $localHandler;
        $this->connectors = $connectors;
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
            SendWebhooks::class => [
                ['localDispatch', 5],
                ['dispatch', 0],
            ],
        ];
    }

    /**
     * Handle event dispatch.
     *
     * @param Message\AbstractMessage $message
     */
    public function __invoke(Message\AbstractMessage $message): void
    {
        if ($message instanceof Message\DispatchWebhookMessage) {
            $webhook = $this->em->find(Entity\StationWebhook::class, $message->webhook_id);

            if (!($webhook instanceof Entity\StationWebhook)) {
                return;
            }

            $event = new SendWebhooks(
                $webhook->getStation(),
                $message->np,
                $message->is_standalone,
                $message->triggers
            );

            $this->dispatchWebhook($event, $webhook);
        }
    }

    /**
     * Always dispatch the special "local" updater task for standalone updates.
     *
     * @param SendWebhooks $event
     */
    public function localDispatch(SendWebhooks $event): void
    {
        $this->localHandler->dispatch($event);
    }

    /**
     * Determine which webhooks to dispatch for a given change in Now Playing data, and dispatch them.
     *
     * @param SendWebhooks $event
     */
    public function dispatch(SendWebhooks $event): void
    {
        if (Settings::getInstance()->isTesting()) {
            $this->logger->info('In testing mode; no webhooks dispatched.');
            return;
        }

        // Assemble list of webhooks for the station
        $stationWebhooks = $event->getStation()->getWebhooks();
        if (0 === $stationWebhooks->count()) {
            return;
        }

        /** @var Entity\StationWebhook[] $enabledWebhooks */
        $enabledWebhooks = $stationWebhooks->filter(function ($webhook) {
            /** @var Entity\StationWebhook $webhook */
            return $webhook->isEnabled();
        });

        $this->logger->debug('Triggering events: ' . implode(', ', $event->getTriggers()));

        // Trigger all appropriate webhooks.
        foreach ($enabledWebhooks as $webhook) {
            $message = new Message\DispatchWebhookMessage();
            $message->webhook_id = $webhook->getId();
            $message->np = $event->getNowPlaying();
            $message->triggers = $event->getTriggers();
            $message->is_standalone = $event->isStandalone();

            $this->messageBus->dispatch($message);
        }
    }

    /**
     * Send a "test" dispatch of the web hook, regardless of whether it is currently enabled, and
     * return any logging information this yields.
     *
     * @param Entity\Station $station
     * @param Entity\StationWebhook $webhook
     *
     * @throws Exception
     */
    public function testDispatch(Entity\Station $station, Entity\StationWebhook $webhook): TestHandler
    {
        $handler = new TestHandler(Logger::DEBUG, false);
        $this->logger->pushHandler($handler);

        $np = $station->getNowplaying();
        $np->resolveUrls($this->router->getBaseUrl(false));
        $np->cache = 'event';

        $event = new SendWebhooks($station, $np, true, $webhook->getTriggers());
        $this->dispatchWebhook($event, $webhook);

        $this->logger->popHandler();

        return $handler;
    }

    protected function dispatchWebhook(
        SendWebhooks $event,
        Entity\StationWebhook $webhook
    ): void {
        $connectorObj = $this->connectors->getConnector($webhook->getType());

        if ($connectorObj->shouldDispatch($event, $webhook)) {
            $this->logger->debug(sprintf('Dispatching connector "%s".', $webhook->getType()));

            $connectorObj->dispatch($event, $webhook);
        }
    }
}
