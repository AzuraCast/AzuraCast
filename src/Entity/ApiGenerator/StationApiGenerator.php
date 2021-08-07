<?php

declare(strict_types=1);

namespace App\Entity\ApiGenerator;

use App\Entity;
use App\Http\Router;
use App\Radio\Adapters;
use Psr\Http\Message\UriInterface;

class StationApiGenerator
{
    public function __construct(
        protected Adapters $adapters,
        protected Router $router
    ) {
    }

    public function __invoke(
        Entity\Station $station,
        ?UriInterface $baseUri = null,
        bool $showAllMounts = false
    ): Entity\Api\Station {
        $fa = $this->adapters->getFrontendAdapter($station);
        $remoteAdapters = $this->adapters->getRemoteAdapters($station);

        $response = new Entity\Api\Station();
        $response->id = (int)$station->getId();
        $response->name = (string)$station->getName();
        $response->shortcode = (string)$station->getShortName();
        $response->description = (string)$station->getDescription();
        $response->frontend = (string)$station->getFrontendType();
        $response->backend = (string)$station->getBackendType();
        $response->url = $station->getUrl();
        $response->is_public = $station->getEnablePublicPage();
        $response->listen_url = $fa->getStreamUrl($station, $baseUri);

        $response->public_player_url = (string)$this->router->named(
            'public:index',
            ['station_id' => $station->getShortName()]
        );
        $response->playlist_pls_url = (string)$this->router->named(
            'public:playlist',
            ['station_id' => $station->getShortName(), 'format' => 'pls']
        );
        $response->playlist_m3u_url = (string)$this->router->named(
            'public:playlist',
            ['station_id' => $station->getShortName(), 'format' => 'm3u']
        );

        $mounts = [];
        if ($fa->supportsMounts() && $station->getMounts()->count() > 0) {
            foreach ($station->getMounts() as $mount) {
                if ($showAllMounts || $mount->isVisibleOnPublicPages()) {
                    $mounts[] = $mount->api($fa, $baseUri);
                }
            }
        }
        $response->mounts = $mounts;

        $remotes = [];
        foreach ($remoteAdapters as $ra_proxy) {
            $remote = $ra_proxy->getRemote();
            if ($showAllMounts || $remote->isVisibleOnPublicPages()) {
                $remotes[] = $remote->api($ra_proxy->getAdapter());
            }
        }
        $response->remotes = $remotes;

        return $response;
    }
}
