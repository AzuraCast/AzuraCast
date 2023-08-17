<?php

declare(strict_types=1);

namespace App\Webhook\Connector;

use App\Entity\Repository\ListenerRepository;
use App\Entity\Station;
use App\Entity\StationWebhook;
use App\Nginx\CustomUrls;
use GuzzleHttp\Client;

abstract class AbstractGoogleAnalyticsConnector extends AbstractConnector
{
    public function __construct(
        Client $httpClient,
        protected readonly ListenerRepository $listenerRepo
    ) {
        parent::__construct($httpClient);
    }

    protected function webhookShouldTrigger(StationWebhook $webhook, array $triggers = []): bool
    {
        return true;
    }

    protected function buildListenUrls(Station $station): array
    {
        $listenBaseUrl = CustomUrls::getListenUrl($station);
        $hlsBaseUrl = CustomUrls::getHlsUrl($station);

        $mountUrls = [];
        foreach ($station->getMounts() as $mount) {
            $mountUrls[$mount->getIdRequired()] = $listenBaseUrl . $mount->getName();
        }

        $remoteUrls = [];
        foreach ($station->getRemotes() as $remote) {
            $remoteUrls[$remote->getIdRequired()] = $listenBaseUrl . '/remote' . $remote->getMount();
        }

        $hlsUrls = [];
        foreach ($station->getHlsStreams() as $hlsStream) {
            $hlsUrls[$hlsStream->getIdRequired()] = $hlsBaseUrl . '/' . $hlsStream->getName();
        }

        return [
            'mounts' => $mountUrls,
            'remotes' => $remoteUrls,
            'hls' => $hlsUrls,
        ];
    }

    protected function getListenUrl(
        array $listener,
        array $listenUrls
    ): ?string {
        if (!empty($listener['mount_id'])) {
            return $listenUrls['mounts'][$listener['mount_id']] ?? null;
        }
        if (!empty($listener['remote_id'])) {
            return $listenUrls['remotes'][$listener['remote_id']] ?? null;
        }
        if (!empty($listener['hls_stream_id'])) {
            return $listenUrls['hls'][$listener['hls_stream_id']] ?? null;
        }

        return null;
    }
}
