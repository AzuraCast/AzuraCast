<?php
namespace App\Webhook;

use App\Entity;
use App\Event\SendWebhooks;
use Azura\Exception;
use App\Provider\WebhookProvider;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Pimple\Psr11\ServiceLocator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class Dispatcher
 * @package App\Webhook
 * @see WebhookProvider
 */
class Dispatcher implements EventSubscriberInterface
{
    /** @var Logger */
    protected $logger;

    /** @var ServiceLocator */
    protected $connectors;

    public function __construct(Logger $logger, ServiceLocator $connectors)
    {
        $this->logger = $logger;
        $this->connectors = $connectors;
    }

    public static function getSubscribedEvents()
    {
        if (APP_TESTING_MODE) {
            return [];
        }

        return [
            SendWebhooks::NAME => [
                ['dispatch', 0],
            ],
        ];
    }

    /**
     * Determine which webhooks to dispatch for a given change in Now Playing data, and dispatch them.
     *
     * @param SendWebhooks $event
     */
    public function dispatch(SendWebhooks $event): void
    {
        if (APP_TESTING_MODE) {
            $this->logger->info('In testing mode; no webhooks dispatched.');
            return;
        }

        // Compile list of connectors for the station. Always dispatch to the local websocket receiver.
        $connectors = [];

        if ($event->isStandalone()) {
            $connectors[] = [
                'type' => 'local',
                'triggers' => [],
                'config' => [],
            ];
        }

        // Assemble list of webhooks for the station
        $station_webhooks = $event->getStation()->getWebhooks();

        if ($station_webhooks->count() > 0) {
            foreach($station_webhooks as $webhook) {
                /** @var Entity\StationWebhook $webhook */
                if ($webhook->isEnabled()) {
                    $connectors[] = [
                        'type' => $webhook->getType(),
                        'triggers' => $webhook->getTriggers() ?: [],
                        'config' => $webhook->getConfig() ?: [],
                    ];
                }
            }
        }

        $this->logger->debug('Triggering events: '.implode(', ', $event->getTriggers()));

        // Trigger all appropriate webhooks.
        foreach($connectors as $connector) {
            if (!$this->connectors->has($connector['type'])) {
                $this->logger->error(sprintf('Webhook connector "%s" does not exist; skipping.', $connector['type']));
                continue;
            }

            /** @var Connector\ConnectorInterface $connector_obj */
            $connector_obj = $this->connectors->get($connector['type']);

            if ($connector_obj->shouldDispatch($event, (array)$connector['triggers'])) {
                $this->logger->debug(sprintf('Dispatching connector "%s".', $connector['type']));

                $connector_obj->dispatch($event, (array)$connector['config']);
            }
        }
    }

    /**
     * Send a "test" dispatch of the web hook, regardless of whether it is currently enabled, and
     * return any logging information this yields.
     *
     * @param Entity\Station $station
     * @param Entity\StationWebhook $webhook
     * @return TestHandler
     * @throws Exception
     */
    public function testDispatch(Entity\Station $station, Entity\StationWebhook $webhook)
    {
        $webhook_type = $webhook->getType();
        $webhook_config = $webhook->getConfig();

        if (!$this->connectors->has($webhook_type)) {
            throw new Exception(sprintf('Webhook connector "%s" does not exist; skipping.', $webhook_type));
        }

        $handler = new TestHandler(Logger::DEBUG, false);
        $this->logger->pushHandler($handler);

        /** @var Connector\ConnectorInterface $connector_obj */
        $connector_obj = $this->connectors->get($webhook_type);

        $np = $station->getNowplaying();

        $event = new SendWebhooks($station, $np);
        $connector_obj->dispatch($event, $webhook_config);

        $this->logger->popHandler();

        return $handler;
    }

    /**
     * Directly access a webhook connector of the specified type.
     *
     * @param $type
     * @return Connector\ConnectorInterface
     */
    public function getConnector($type): Connector\ConnectorInterface
    {
        if ($this->connectors->has($type)) {
            return $this->connectors->get($type);
        }

        throw new \InvalidArgumentException('Invalid web hook connector type specified.');
    }
}
