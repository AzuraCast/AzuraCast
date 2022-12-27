<?php

declare(strict_types=1);

namespace App\Webhook\Connector;

use App\Entity\Api\NowPlaying\NowPlaying;
use App\Entity\Repository\ListenerRepository;
use App\Entity\Station;
use App\Entity\StationWebhook;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Uri;
use Monolog\Logger;
use TheIconic\Tracking\GoogleAnalytics\Analytics;
use TheIconic\Tracking\GoogleAnalytics\Network\HttpClient;

final class GoogleAnalytics extends AbstractConnector
{
    public const NAME = 'google_analytics';

    public function __construct(
        Logger $logger,
        Client $httpClient,
        private readonly ListenerRepository $listenerRepo
    ) {
        parent::__construct($logger, $httpClient);
    }

    /**
     * @inheritDoc
     */
    public function dispatch(
        Station $station,
        StationWebhook $webhook,
        NowPlaying $np,
        array $triggers
    ): void {
        $config = $webhook->getConfig();
        if (empty($config['tracking_id'])) {
            throw $this->incompleteConfigException(self::NAME);
        }

        // Get listen URLs for each mount point.
        $radioPort = $station->getFrontendConfig()->getPort();

        $mountUrls = [];
        foreach ($station->getMounts() as $mount) {
            $mountUrl = (new Uri())
                ->withPath('/radio/' . $radioPort . $mount->getName());
            $mountUrls[$mount->getId()] = (string)$mountUrl;
        }

        $remoteUrls = [];
        foreach ($station->getRemotes() as $remote) {
            $remoteUrl = (new Uri())
                ->withPath('/radio/remote' . $remote->getMount());
            $remoteUrls[$remote->getId()] = (string)$remoteUrl;
        }

        // Build analytics
        $httpClient = new HttpClient();
        $httpClient->setClient($this->httpClient);

        $analytics = new Analytics(true);
        $analytics->setHttpClient($httpClient);

        $analytics->setProtocolVersion('1')
            ->setTrackingId($config['tracking_id']);

        // Get all current listeners
        $liveListeners = $this->listenerRepo->iterateLiveListenersArray($station);

        $i = 0;
        foreach ($liveListeners as $listener) {
            $listenerUrl = null;
            if (!empty($listener['mount_id'])) {
                $listenerUrl = $mountUrls[$listener['mount_id']] ?? null;
            } elseif (!empty($listener['remote_id'])) {
                $listenerUrl = $remoteUrls[$listener['remote_id']] ?? null;
            }

            if (null === $listenerUrl) {
                continue;
            }

            $analytics->setClientId($listener['listener_uid']);
            $analytics->setUserAgentOverride($listener['listener_user_agent']);
            $analytics->setIpOverride($listener['listener_ip']);
            $analytics->setDocumentPath($listenerUrl);
            $analytics->__call('enqueuePageView', []);
            $i++;

            if (20 === $i) {
                $analytics->sendEnqueuedHits();
                $i = 0;
            }
        }

        $analytics->sendEnqueuedHits();
    }
}
