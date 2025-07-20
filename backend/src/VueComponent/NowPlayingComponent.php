<?php

declare(strict_types=1);

namespace App\VueComponent;

use App\Http\ServerRequest;
use App\Service\Centrifugo;

final class NowPlayingComponent implements VueComponentInterface
{
    public function __construct(
        private readonly Centrifugo $centrifugo
    ) {
    }

    public function getProps(ServerRequest $request): array
    {
        $customization = $request->getCustomization();

        $station = $request->getStation();
        $backendConfig = $station->backend_config;

        return [
            ...$this->getDataProps($request),
            'offlineText' => $station->branding_config->offline_text,
            'showAlbumArt' => !$customization->hideAlbumArt(),
            'autoplay' => !empty($request->getQueryParam('autoplay')),
            'showHls' => $backendConfig->hls_enable_on_public_player,
        ];
    }

    public function getDataProps(ServerRequest $request): array
    {
        $station = $request->getStation();
        $customization = $request->getCustomization();

        return [
            'stationShortName' => $station->short_name,
            'useStatic' => $customization->useStaticNowPlaying(),
            'useSse' => $customization->useStaticNowPlaying() && $this->centrifugo->isSupported(),
        ];
    }
}
