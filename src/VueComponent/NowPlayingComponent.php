<?php

declare(strict_types=1);

namespace App\VueComponent;

use App\Entity\ApiGenerator\NowPlayingApiGenerator;
use App\Http\ServerRequest;
use App\Service\Centrifugo;

final class NowPlayingComponent implements VueComponentInterface
{
    public function __construct(
        private readonly NowPlayingApiGenerator $npApiGenerator,
        private readonly Centrifugo $centrifugo
    ) {
    }

    public function getProps(ServerRequest $request): array
    {
        $station = $request->getStation();

        $baseUrl = $request->getRouter()->getBaseUrl();

        $np = $this->npApiGenerator->currentOrEmpty($station);
        $np->resolveUrls($baseUrl);

        $customization = $request->getCustomization();

        $backendConfig = $station->getBackendConfig();

        return [
            ...$this->getDataProps($request),
            'showAlbumArt' => !$customization->hideAlbumArt(),
            'autoplay' => !empty($request->getQueryParam('autoplay')),
            'showHls' => $backendConfig->getHlsEnableOnPublicPlayer(),
            'hlsIsDefault' => $backendConfig->getHlsIsDefault(),
        ];
    }

    public function getDataProps(ServerRequest $request): array
    {
        $station = $request->getStation();

        $baseUrl = $request->getRouter()->getBaseUrl();

        $np = $this->npApiGenerator->currentOrEmpty($station);
        $np->resolveUrls($baseUrl);

        $customization = $request->getCustomization();
        $router = $request->getRouter();

        $props = [
            'initialNowPlaying' => $np,
            'nowPlayingUri' => $customization->useStaticNowPlaying()
                ? '/api/nowplaying_static/' . urlencode($station->getShortName()) . '.json'
                : $router->named('api:nowplaying:index', ['station_id' => $station->getShortName()]),
            'timeUri' => $router->named('api:index:time'),
            'useSse' => false,
        ];

        if ($customization->useStaticNowPlaying() && $this->centrifugo->isSupported()) {
            $props['useSse'] = true;
            $props['sseUri'] = $this->centrifugo->getSseUrl($station);
        }

        return $props;
    }
}
