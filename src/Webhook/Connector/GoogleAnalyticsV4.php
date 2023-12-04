<?php

declare(strict_types=1);

namespace App\Webhook\Connector;

use App\Entity\Api\NowPlaying\NowPlaying;
use App\Entity\Station;
use App\Entity\StationWebhook;
use App\Utilities\Types;
use Br33f\Ga4\MeasurementProtocol\Dto\Event\BaseEvent;
use Br33f\Ga4\MeasurementProtocol\Dto\Request\BaseRequest;
use Br33f\Ga4\MeasurementProtocol\HttpClient as Ga4HttpClient;
use Br33f\Ga4\MeasurementProtocol\Service;

final class GoogleAnalyticsV4 extends AbstractGoogleAnalyticsConnector
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

        $apiSecret = Types::stringOrNull($config['api_secret'], true);
        $measurementId = Types::stringOrNull($config['measurement_id'], true);

        if (null === $apiSecret) {
            throw $this->incompleteConfigException($webhook);
        }

        // Get listen URLs for each mount point.
        $listenUrls = $this->buildListenUrls($station);

        // Build analytics
        $gaHttpClient = new Ga4HttpClient();
        $gaHttpClient->setClient($this->httpClient);

        $ga4Service = new Service($apiSecret, $measurementId);
        $ga4Service->setHttpClient($gaHttpClient);

        // Get all current listeners
        $liveListeners = $this->listenerRepo->iterateLiveListenersArray($station);

        foreach ($liveListeners as $listener) {
            $listenerUrl = $this->getListenUrl($listener, $listenUrls);
            if (null === $listenerUrl) {
                continue;
            }

            $event = new BaseEvent('page_view');
            $event->setParamValue('page_location', $listenerUrl)
                ->setParamValue('page_title', $listenerUrl)
                ->setParamValue('ip', $listener['listener_ip'])
                ->setParamValue('user_agent', $listener['listener_user_agent']);

            $ga4Service->send(
                new BaseRequest(
                    (string)$listener['listener_uid'],
                    $event
                )
            );
        }
    }
}
