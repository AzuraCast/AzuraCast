<?php

declare(strict_types=1);

namespace App\VueComponent;

use App\Entity\Api\NowPlaying\Vue\NowPlayingProps;
use App\Http\ServerRequest;
use App\Service\Centrifugo;

final readonly class NowPlayingComponent implements VueComponentInterface
{
    public function __construct(
        private Centrifugo $centrifugo
    ) {
    }

    public function getProps(ServerRequest $request): array
    {
        $customization = $request->getCustomization();

        $station = $request->getStation();
        $backendConfig = $station->backend_config;

        return [
            'nowPlayingProps' => $this->getDataProps($request),
            'offlineText' => $station->branding_config->offline_text,
            'showAlbumArt' => !$customization->hideAlbumArt(),
            'autoplay' => !empty($request->getQueryParam('autoplay')),
            'showHls' => $backendConfig->hls_enable_on_public_player,
        ];
    }

    public function getDataProps(ServerRequest $request): NowPlayingProps
    {
        $station = $request->getStation();
        $customization = $request->getCustomization();

        return new NowPlayingProps(
            stationShortName: $station->short_name,
            useStatic: $customization->useStaticNowPlaying(),
            useSse: $customization->useStaticNowPlaying() && $this->centrifugo->isSupported()
        );
    }
}
