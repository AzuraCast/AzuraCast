<?php

declare(strict_types=1);

namespace App\Entity\ApiGenerator;

use App\Entity\Api\NowPlaying\Station as NowPlayingStation;
use App\Entity\Api\ResolvableUrl;
use App\Entity\Station;
use App\Http\Router;
use App\Radio\Adapters;
use Psr\Http\Message\UriInterface;

final readonly class StationApiGenerator
{
    public function __construct(
        private Adapters $adapters,
        private Router $router
    ) {
    }

    public function __invoke(
        Station $station,
        ?UriInterface $baseUri = null,
        bool $showAllMounts = false
    ): NowPlayingStation {
        $frontend = $this->adapters->getFrontendAdapter($station);
        $backend = $this->adapters->getBackendAdapter($station);

        $response = new NowPlayingStation();
        $response->id = $station->id;
        $response->name = $station->name;
        $response->shortcode = $station->short_name;
        $response->description = (string)$station->description;
        $response->frontend = $station->frontend_type->value;
        $response->backend = $station->backend_type->value;
        $response->timezone = $station->timezone;
        $response->url = $station->url;
        $response->is_public = $station->enable_public_page;
        $response->requests_enabled = $station->enable_requests;

        $response->public_player_url = new ResolvableUrl(
            $this->router->namedAsUri(
                'public:index',
                ['station_id' => $station->short_name]
            )
        );
        $response->playlist_pls_url = new ResolvableUrl(
            $this->router->namedAsUri(
                'public:playlist',
                ['station_id' => $station->short_name, 'format' => 'pls']
            )
        );
        $response->playlist_m3u_url = new ResolvableUrl(
            $this->router->namedAsUri(
                'public:playlist',
                ['station_id' => $station->short_name, 'format' => 'm3u']
            )
        );

        $mounts = [];
        if (
            null !== $frontend
            && $station->frontend_type->supportsMounts()
            && $station->mounts->count() > 0
        ) {
            foreach ($station->mounts as $mount) {
                if ($showAllMounts || $mount->is_visible_on_public_pages) {
                    $mounts[] = $mount->api($frontend, $baseUri);
                }
            }
        }
        $response->mounts = $mounts;

        $remotes = [];
        foreach ($station->remotes as $remote) {
            if ($showAllMounts || $remote->is_visible_on_public_pages) {
                $remotes[] = $remote->api(
                    $this->adapters->getRemoteAdapter($remote)
                );
            }
        }

        $response->remotes = $remotes;

        // Pull the "listen URL" from the best available source for the station.
        $response->listen_url = match (true) {
            (null !== $frontend) => new ResolvableUrl($frontend->getStreamUrl($station, $baseUri)),
            (count($remotes) > 0) => $remotes[0]->url,
            default => null
        };

        $response->hls_enabled = $station->backend_type->isEnabled() && $station->enable_hls;
        $response->hls_is_default = $response->hls_enabled && $station->backend_config->hls_is_default;

        $response->hls_url = (null !== $backend && $response->hls_enabled)
            ? new ResolvableUrl($backend->getHlsUrl($station, $baseUri))
            : null;

        $hlsListeners = 0;
        foreach ($station->hls_streams as $hlsStream) {
            $hlsListeners += $hlsStream->listeners;
        }
        $response->hls_listeners = $hlsListeners;

        return $response;
    }
}
