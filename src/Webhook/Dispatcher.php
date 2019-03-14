<?php
namespace App\Webhook;

use App\Entity;
use App\Event\SendWebhooks;
use App\Http\Router;
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

    /** @var Router */
    protected $router;

    /** @var ServiceLocator */
    protected $connectors;

    public function __construct(Logger $logger, Router $router, ServiceLocator $connectors)
    {
        $this->logger = $logger;
        $this->router = $router;
        $this->connectors = $connectors;
    }

    public static function getSubscribedEvents()
    {
        if (APP_TESTING_MODE) {
            return [];
        }

        return [
            SendWebhooks::NAME => [
                ['resolveNowPlayingUrls', 100],
                ['dispatch', 0],
            ],
        ];
    }

    /**
     * Resolve URLs that are included in the NowPlaying response.
     *
     * @param SendWebhooks $event
     */
    public function resolveNowPlayingUrls(SendWebhooks $event): void
    {
        $np = $event->getNowPlaying();
        $np->resolveUrls($this->router);
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

        // Assemble list of webhooks for the station
        $station_webhooks = $event->getStation()->getWebhooks();

        /** @var Entity\StationWebhook[] $connectors */
        $connectors = [];
        if ($station_webhooks->count() > 0) {
            foreach($station_webhooks as $webhook) {
                /** @var Entity\StationWebhook $webhook */
                if ($webhook->isEnabled()) {
                    $connectors[] = $webhook;
                }
            }
        }

        $this->logger->debug('Triggering events: '.implode(', ', $event->getTriggers()));

        // Always dispatch the special "local" updater task for standalone updates.
        if ($event->isStandalone()) {
            /** @var Connector\Local $connector_obj */
            $connector_obj = $this->connectors->get(Connector\Local::NAME);
            $connector_obj->dispatch($event);
        }

        // Trigger all appropriate webhooks.
        foreach($connectors as $connector) {
            if (!$this->connectors->has($connector->getType())) {
                $this->logger->error(sprintf('Webhook connector "%s" does not exist; skipping.', $connector->getType()));
                continue;
            }

            /** @var Connector\ConnectorInterface $connector_obj */
            $connector_obj = $this->connectors->get($connector->getType());

            if ($connector_obj->shouldDispatch($event, $connector)) {
                $this->logger->debug(sprintf('Dispatching connector "%s".', $connector['type']));

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
     * @return TestHandler
     * @throws Exception
     */
    public function testDispatch(Entity\Station $station, Entity\StationWebhook $webhook)
    {
        $webhook_type = $webhook->getType();

        if (!$this->connectors->has($webhook_type)) {
            throw new Exception(sprintf('Webhook connector "%s" does not exist; skipping.', $webhook_type));
        }

        $handler = new TestHandler(Logger::DEBUG, false);
        $this->logger->pushHandler($handler);

        /** @var Connector\ConnectorInterface $connector_obj */
        $connector_obj = $this->connectors->get($webhook_type);

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
