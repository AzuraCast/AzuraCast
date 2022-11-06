<?php

declare(strict_types=1);

namespace App\Entity\ApiGenerator;

use App\Entity;
use App\Http\Router;
use App\Radio\Adapters;
use Psr\Http\Message\UriInterface;

final class StationApiGenerator
{
    public function __construct(
        private readonly Adapters $adapters,
        private readonly Router $router
    ) {
    }

    public function __invoke(
        Entity\Station $station,
        ?UriInterface $baseUri = null,
        bool $showAllMounts = false
    ): Entity\Api\NowPlaying\Station {
        $frontend = $this->adapters->getFrontendAdapter($station);
        $backend = $this->adapters->getBackendAdapter($station);

        $response = new Entity\Api\NowPlaying\Station();
        $response->id = (int)$station->getId();
        $response->name = (string)$station->getName();
        $response->shortcode = $station->getShortName();
        $response->description = (string)$station->getDescription();
        $response->frontend = (string)$station->getFrontendType();
        $response->backend = (string)$station->getBackendType();
        $response->url = $station->getUrl();
        $response->is_public = $station->getEnablePublicPage();
        $response->listen_url = $frontend?->getStreamUrl($station, $baseUri);

        $response->public_player_url = $this->router->named(
            'public:index',
            ['station_id' => $station->getShortName()]
        );
        $response->playlist_pls_url = $this->router->named(
            'public:playlist',
            ['station_id' => $station->getShortName(), 'format' => 'pls']
        );
        $response->playlist_m3u_url = $this->router->named(
            'public:playlist',
            ['station_id' => $station->getShortName(), 'format' => 'm3u']
        );

        $mounts = [];
        if (
            null !== $frontend && $station->getFrontendTypeEnum()->supportsMounts() && $station->getMounts()->count(
            ) > 0
        ) {
            foreach ($station->getMounts() as $mount) {
                if ($showAllMounts || $mount->getIsVisibleOnPublicPages()) {
                    $mounts[] = $mount->api($frontend, $baseUri);
                }
            }
        }
        $response->mounts = $mounts;

        $remotes = [];
        foreach ($station->getRemotes() as $remote) {
            if ($showAllMounts || $remote->getIsVisibleOnPublicPages()) {
                $remotes[] = $remote->api(
                    $this->adapters->getRemoteAdapter($station, $remote)
                );
            }
        }

        $response->remotes = $remotes;

        $response->hls_enabled = $station->getBackendTypeEnum()->isEnabled() && $station->getEnableHls();
        $response->hls_url = (null !== $backend && $response->hls_enabled)
            ? $backend->getHlsUrl($station, $baseUri)
            : null;

        $hlsListeners = 0;
        foreach ($station->getHlsStreams() as $hlsStream) {
            $hlsListeners += $hlsStream->getListeners();
        }
        $response->hls_listeners = $hlsListeners;

        return $response;
    }
}
