<?php

namespace App\Entity\ApiGenerator;

use App\Entity;
use App\Radio\Adapters;
use Psr\Http\Message\UriInterface;

class StationApiGenerator
{
    protected Adapters $adapters;

    public function __construct(Adapters $adapters)
    {
        $this->adapters = $adapters;
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
        $response->is_public = $station->getEnablePublicPage();
        $response->listen_url = $fa->getStreamUrl($station, $baseUri);

        $mounts = [];
        if ($fa::supportsMounts() && $station->getMounts()->count() > 0) {
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
