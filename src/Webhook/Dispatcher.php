<?php
namespace App\Webhook;

use App\Config;
use App\Entity;
use App\Exception;
use App\Provider\WebhookProvider;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Pimple\Psr11\ServiceLocator;

/**
 * Class Dispatcher
 * @package App\Webhook
 * @see WebhookProvider
 */
class Dispatcher
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

    /**
     * Determine which webhooks to dispatch for a given change in Now Playing data, and dispatch them.
     *
     * @param Entity\Station $station
     * @param Entity\Api\NowPlaying $np_old
     * @param Entity\Api\NowPlaying $np_new
     * @param boolean $is_standalone
     */
    public function dispatch(Entity\Station $station, Entity\Api\NowPlaying $np_old, Entity\Api\NowPlaying $np_new, $is_standalone = true): void
    {
        $this->logger->pushProcessor(function($record) use ($station) {
            $record['extra']['station'] = [
                'id' => $station->getId(),
                'name' => $station->getName(),
            ];
            return $record;
        });

        if (APP_TESTING_MODE) {
            $this->logger->info('In testing mode; no webhooks dispatched.');
            $this->logger->popProcessor();
            return;
        }

        // Compile list of connectors for the station. Always dispatch to the local websocket receiver.
        $connectors = [];

        if ($is_standalone) {
            $connectors[] = [
                'type' => 'local',
                'triggers' => [],
                'config' => [],
            ];
        }

        // Assemble list of webhooks for the station
        $station_webhooks = $station->getWebhooks();

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

        // Determine which events should be triggered as a result of this change.
        $to_trigger = ['all'];

        if ($np_old->now_playing->song->id !== $np_new->now_playing->song->id) {
            $to_trigger[] = 'song_changed';
        }

        if ($np_old->listeners->current > $np_new->listeners->current) {
            $to_trigger[] = 'listener_lost';
        } elseif ($np_old->listeners->current < $np_new->listeners->current) {
            $to_trigger[] = 'listener_gained';
        }

        if ($np_old->live->is_live === false && $np_new->live->is_live === true) {
            $to_trigger[] = 'live_connect';
        } elseif ($np_old->live->is_live === true && $np_new->live->is_live === false) {
            $to_trigger[] = 'live_disconnect';
        }

        $this->logger->debug('Triggering events: '.implode(', ', $to_trigger));

        // Trigger all appropriate webhooks.
        foreach($connectors as $connector) {
            if (!$this->connectors->has($connector['type'])) {
                $this->logger->error(sprintf('Webhook connector "%s" does not exist; skipping.', $connector['type']));
                continue;
            }

            /** @var Connector\ConnectorInterface $connector_obj */
            $connector_obj = $this->connectors->get($connector['type']);

            if ($connector_obj->shouldDispatch($to_trigger, (array)$connector['triggers'])) {
                $this->logger->debug(sprintf('Dispatching connector "%s".', $connector['type']));

                $connector_obj->dispatch($station, $np_new, (array)$connector['config']);
            }
        }

        $this->logger->popProcessor();
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

        $connector_obj->dispatch($station, $np, $webhook_config);

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
