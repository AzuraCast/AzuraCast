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
        $backendConfig = $station->getBackendConfig();

        return [
            ...$this->getDataProps($request),
            'showAlbumArt' => !$customization->hideAlbumArt(),
            'autoplay' => !empty($request->getQueryParam('autoplay')),
            'showHls' => $backendConfig->getHlsEnableOnPublicPlayer(),
        ];
    }

    public function getDataProps(ServerRequest $request): array
    {
        $station = $request->getStation();
        $customization = $request->getCustomization();

        return [
            'stationShortName' => $station->getShortName(),
            'useStatic' => $customization->useStaticNowPlaying(),
            'useSse' => $customization->useStaticNowPlaying() && $this->centrifugo->isSupported(),
        ];
    }
}
