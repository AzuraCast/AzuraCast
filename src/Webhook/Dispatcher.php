<?php
namespace App\Webhook;

use App\Entity;
use App\Event\SendWebhooks;
use App\Settings;
use Azura\Exception;
use InvalidArgumentException;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @package App\Webhook
 * @see WebhookProvider
 */
class Dispatcher implements EventSubscriberInterface
{
    protected Logger $logger;

    /** @var Connector\ConnectorInterface[] */
    protected array $connectors;

    public function __construct(Logger $logger, array $connectors)
    {
        $this->logger = $logger;
        $this->connectors = $connectors;
    }

    public static function getSubscribedEvents()
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
     * Always dispatch the special "local" updater task for standalone updates.
     *
     * @param SendWebhooks $event
     */
    public function localDispatch(SendWebhooks $event): void
    {
        /** @var Connector\Local $connector_obj */
        $connector_obj = $this->connectors[Connector\Local::NAME];
        $connector_obj->dispatch($event);
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
        $station_webhooks = $event->getStation()->getWebhooks();

        if (0 === $station_webhooks->count()) {
            return;
        }

        /** @var Entity\StationWebhook[] $connectors */
        $connectors = [];
        foreach ($station_webhooks as $webhook) {
            /** @var Entity\StationWebhook $webhook */
            if ($webhook->isEnabled()) {
                $connectors[] = $webhook;
            }
        }

        $this->logger->debug('Triggering events: ' . implode(', ', $event->getTriggers()));

        // Trigger all appropriate webhooks.
        foreach ($connectors as $connector) {
            if (!isset($this->connectors[$connector->getType()])) {
                $this->logger->error(sprintf('Webhook connector "%s" does not exist; skipping.',
                    $connector->getType()));
                continue;
            }

            /** @var Connector\ConnectorInterface $connector_obj */
            $connector_obj = $this->connectors[$connector->getType()];

            if ($connector_obj->shouldDispatch($event, $connector)) {
                $this->logger->debug(sprintf('Dispatching connector "%s".', $connector->getType()));

                $connector_obj->dispatch($event, $connector);
            }
        }
    }

    /**
     * Send a "test" dispatch of the web hook, regardless of whether it is currently enabled, and
     * return any logging information this yields.
     *
     * @param Entity\Station $station
     * @param Entity\StationWebhook $webhook
     *
     * @return TestHandler
     * @throws Exception
     */
    public function testDispatch(Entity\Station $station, Entity\StationWebhook $webhook)
    {
        $webhook_type = $webhook->getType();

        if (!isset($this->connectors[$webhook_type])) {
            throw new Exception(sprintf('Webhook connector "%s" does not exist; skipping.', $webhook_type));
        }

        $handler = new TestHandler(Logger::DEBUG, false);
        $this->logger->pushHandler($handler);

        /** @var Connector\ConnectorInterface $connector_obj */
        $connector_obj = $this->connectors[$webhook_type];

        $np = $station->getNowplaying();

        $event = new SendWebhooks($station, $np);
        $connector_obj->dispatch($event, $webhook);

        $this->logger->popHandler();

        return $handler;
    }

    /**
     * Directly access a webhook connector of the specified type.
     *
     * @param string $type
     *
     * @return Connector\ConnectorInterface
     */
    public function getConnector($type): Connector\ConnectorInterface
    {
        if (isset($this->connectors[$type])) {
            return $this->connectors[$type];
        }

        throw new InvalidArgumentException('Invalid web hook connector type specified.');
    }
}
