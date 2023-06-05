<?php

declare(strict_types=1);

namespace App\Webhook\Connector;

use App\Entity\Api\NowPlaying\NowPlaying;
use App\Entity\Station;
use App\Entity\StationWebhook;
use TheIconic\Tracking\GoogleAnalytics\Analytics;
use TheIconic\Tracking\GoogleAnalytics\Network\HttpClient;

final class GoogleAnalyticsV3 extends AbstractGoogleAnalyticsConnector
{
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
            throw $this->incompleteConfigException($webhook);
        }

        // Get listen URLs for each mount point.
        $listenUrls = $this->buildListenUrls($station);

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
            $listenerUrl = $this->getListenUrl($listener, $listenUrls);
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
