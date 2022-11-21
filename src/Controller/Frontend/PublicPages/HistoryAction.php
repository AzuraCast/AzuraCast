<?php

declare(strict_types=1);

namespace App\Controller\Frontend\PublicPages;

use App\Entity;
use App\Exception\StationNotFoundException;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class HistoryAction
{
    public function __construct(
        private readonly Entity\ApiGenerator\NowPlayingApiGenerator $npApiGenerator
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        string $station_id
    ): ResponseInterface {
        $station = $request->getStation();

        if (!$station->getEnablePublicPage()) {
            throw new StationNotFoundException();
        }

        $np = $this->npApiGenerator->currentOrEmpty($station);
        $np->resolveUrls($request->getRouter()->getBaseUrl());

        $customization = $request->getCustomization();
        $router = $request->getRouter();

        $useStatic = $customization->useStaticNowPlaying();

        return $request->getView()->renderVuePage(
            response: $response->withHeader('X-Frame-Options', '*'),
            component: 'Vue_PublicHistory',
            id: 'song-history',
            layout: 'minimal',
            title: __('History') . ' - ' . $station->getName(),
            layoutParams: [
                'page_class' => 'embed station-' . $station->getShortName(),
                'hide_footer' => true,
            ],
            props: [
                'initialNowPlaying' => $np,
                'showAlbumArt' => !$customization->hideAlbumArt(),
                'nowPlayingUri' => $useStatic
                    ? '/api/nowplaying_static/' . urlencode($station->getShortName()) . '.json'
                    : $router->named('api:nowplaying:index', ['station_id' => $station->getShortName()]),
            ],
        );
    }
}
