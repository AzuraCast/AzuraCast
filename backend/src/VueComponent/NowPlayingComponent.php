<?php

declare(strict_types=1);

namespace App\VueComponent;

use App\Entity\Api\NowPlaying\Vue\NowPlayingProps;
use App\Entity\Api\WidgetCustomization;
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

        // Get widget customization from URL parameters
        $widgetCustomization = WidgetCustomization::fromRequest($request);

        return [
            'nowPlayingProps' => $this->getDataProps($request),
            'offlineText' => $station->branding_config->offline_text,
            'showAlbumArt' => $widgetCustomization->showAlbumArt && !$customization->hideAlbumArt(),
            'autoplay' => $widgetCustomization->autoplay,
            'showHls' => $backendConfig->hls_enable_on_public_player,
            'widgetCustomization' => $widgetCustomization,
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
