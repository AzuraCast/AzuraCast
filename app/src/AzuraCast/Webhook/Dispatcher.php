<?php
namespace AzuraCast\Webhook;

use Entity;
use Monolog\Logger;
use Pimple\Psr11\ServiceLocator;

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

    public static function getConnectors()
    {
        return [
            'generic' => [
                'name' => _('Generic Web Hook'),
                'description' => _('Automatically send a message to any URL when your station data changes.'),
            ],
            'tunein' => [
                'name' => _('TuneIn AIR'),
                'description' => _('Send song metadata changes to TuneIn.'),
            ],
            'discord' => [
                'name' => _('Discord Webhook'),
                'description' => _('Automatically send a customized message to your Discord server.'),
            ],
            'twitter' => [
                'name' => _('Twitter Post'),
                'description' => _('Automatically send a tweet.'),
            ],
        ];
    }

    public static function getTriggers()
    {
        return [
            'song_changed' => _('Any time the currently playing song changes'),
            'listener_gained' => _('Any time the listener count increases'),
            'listener_lost' => _('Any time the listener count decreases'),
            'live_connect' => _('Any time a live streamer/DJ connects to the stream'),
            'live_disconnect' => _('Any time a live streamer/DJ disconnects from the stream'),
        ];
    }

}