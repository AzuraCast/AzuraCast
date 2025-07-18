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
        foreach ($station->mounts as $mount) {
            $mountUrls[$mount->id] = $listenBaseUrl . $mount->name;
        }

        $remoteUrls = [];
        foreach ($station->remotes as $remote) {
            $remoteUrls[$remote->id] = $listenBaseUrl . '/remote' . $remote->mount;
        }

        $hlsUrls = [];
        foreach ($station->hls_streams as $hlsStream) {
            $hlsUrls[$hlsStream->id] = $hlsBaseUrl . '/' . $hlsStream->name;
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
