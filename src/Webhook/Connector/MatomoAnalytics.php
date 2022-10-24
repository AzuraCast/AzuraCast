<?php

declare(strict_types=1);

namespace App\Webhook\Connector;

use App\Entity;
use App\Http\RouterInterface;
use App\Utilities\Urls;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\TransferException;
use Monolog\Logger;
use Psr\Http\Message\UriInterface;

final class MatomoAnalytics extends AbstractConnector
{
    public const NAME = 'matomo_analytics';

    public function __construct(
        Logger $logger,
        Client $httpClient,
        private readonly RouterInterface $router,
        private readonly Entity\Repository\ListenerRepository $listenerRepo
    ) {
        parent::__construct($logger, $httpClient);
    }

    /**
     * @inheritDoc
     */
    public function dispatch(
        Entity\Station $station,
        Entity\StationWebhook $webhook,
        Entity\Api\NowPlaying\NowPlaying $np,
        array $triggers
    ): bool {
        $config = $webhook->getConfig();

        if (empty($config['matomo_url']) || empty($config['site_id'])) {
            $this->logger->error('Webhook ' . self::NAME . ' is missing necessary configuration. Skipping...');
            return false;
        }

        // Get listen URLs for each mount point.
        $radioPort = $station->getFrontendConfig()->getPort();

        $baseUri = $this->router->getBaseUrl();

        $mountUrls = [];
        $mountNames = [];
        foreach ($station->getMounts() as $mount) {
            $mountUrl = $baseUri->withPath('/radio/' . $radioPort . $mount->getName());
            $mountUrls[$mount->getId()] = (string)$mountUrl;
            $mountNames[$mount->getId()] = (string)$mount;
        }

        $remoteUrls = [];
        $remoteNames = [];
        foreach ($station->getRemotes() as $remote) {
            $remoteUrl = $baseUri->withPath('/radio/remote' . $remote->getMount());
            $remoteUrls[$remote->getId()] = (string)$remoteUrl;
            $remoteNames[$remote->getId()] = (string)$remote;
        }

        // Build Matomo URI
        $apiUrl = Urls::parseUserUrl(
            $config['matomo_url'],
            'Matomo Analytics URL',
        )->withPath('/matomo.php');

        $apiToken = $config['token'] ?? null;

        $stationName = $station->getName();

        // Get all current listeners
        $liveListeners = $this->listenerRepo->iterateLiveListenersArray($station);
        $webhookLastSent = (int)$webhook->getMetadataKey($webhook::LAST_SENT_TIMESTAMP_KEY, 0);

        $i = 0;
        $entries = [];

        foreach ($liveListeners as $listener) {
            $listenerUrl = null;
            $streamName = 'Stream';

            if (!empty($listener['mount_id'])) {
                $listenerUrl = $mountUrls[$listener['mount_id']] ?? null;
                $streamName = $mountNames[$listener['mount_id']] ?? $streamName;
            } elseif (!empty($listener['remote_id'])) {
                $listenerUrl = $remoteUrls[$listener['remote_id']] ?? null;
                $streamName = $remoteNames[$listener['remote_id']] ?? $streamName;
            }

            if (null === $listenerUrl) {
                continue;
            }

            $entry = [
                'idsite' => (int)$config['site_id'],
                'rec' => 1,
                'action_name' => 'Listeners / ' . $stationName . ' / ' . $streamName,
                'url' => $listenerUrl,
                'rand' => random_int(10000, 99999),
                'apiv' => 1,
                'ua' => $listener['listener_user_agent'],
                'cid' => substr($listener['listener_hash'], 0, 16),
            ];

            // If this listener is already registered, this is a "ping" update.
            if ($listener['timestamp_start'] < $webhookLastSent) {
                $entry['ping'] = 1;
            }

            if (!empty($apiToken)) {
                $entry['cip'] = $listener['listener_ip'];
            }

            $entries[] = $entry;
            $i++;

            if (100 === $i) {
                if (!$this->sendBatch($apiUrl, $apiToken, $entries)) {
                    return false;
                }
                $entries = [];
                $i = 0;
            }
        }

        return $this->sendBatch($apiUrl, $apiToken, $entries);
    }

    private function sendBatch(UriInterface $apiUrl, ?string $apiToken, array $entries): bool
    {
        if (empty($entries)) {
            return true;
        }

        $jsonBody = [
            'requests' => array_map(static function ($row) {
                return '?' . http_build_query($row);
            }, $entries),
        ];

        if (!empty($apiToken)) {
            $jsonBody['token_auth'] = $apiToken;
        }

        $this->logger->debug('Message body for Matomo API Query', ['body' => $jsonBody]);

        try {
            $response = $this->httpClient->post($apiUrl, [
                'json' => $jsonBody,
            ]);

            $this->logger->debug(
                sprintf('Matomo returned code %d', $response->getStatusCode()),
                ['response_body' => $response->getBody()->getContents()]
            );
        } catch (TransferException $e) {
            $this->logger->error(sprintf('Error from Matomo (%d): %s', $e->getCode(), $e->getMessage()));
            return false;
        }

        return true;
    }
}
