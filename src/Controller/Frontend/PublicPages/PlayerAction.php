<?php

declare(strict_types=1);

namespace App\Controller\Frontend\PublicPages;

use App\Entity;
use App\Exception\StationNotFoundException;
use App\Http\Response;
use App\Http\Router;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class PlayerAction
{
    public function __construct(
        private readonly Entity\ApiGenerator\NowPlayingApiGenerator $npApiGenerator,
        private readonly Entity\Repository\CustomFieldRepository $customFieldRepo,
        private readonly Entity\Repository\StationRepository $stationRepo,
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        string $station_id,
        ?string $embed = null,
    ): ResponseInterface {
        $response = $response
            ->withHeader('X-Frame-Options', '*')
            ->withHeader('X-Robots-Tag', 'index, nofollow');

        $station = $request->getStation();

        if (!$station->getEnablePublicPage()) {
            throw new StationNotFoundException();
        }

        $baseUrl = $request->getRouter()->getBaseUrl();

        $np = $this->npApiGenerator->currentOrEmpty($station);
        $np->resolveUrls($baseUrl);

        $defaultAlbumArtUri = $this->stationRepo->getDefaultAlbumArtUrl($station);
        $defaultAlbumArt = Router::resolveUri($baseUrl, $defaultAlbumArtUri, true);

        // Build Vue props.
        $customization = $request->getCustomization();
        $router = $request->getRouter();

        $backendConfig = $station->getBackendConfig();

        $props = [
            'initialNowPlaying' => $np,
            'showAlbumArt' => !$customization->hideAlbumArt(),
            'autoplay' => !empty($request->getQueryParam('autoplay')),
            'showHls' => $backendConfig->getHlsEnableOnPublicPlayer(),
            'hlsIsDefault' => $backendConfig->getHlsIsDefault(),
        ];

        if ($customization->useWebSocketsForNowPlaying()) {
            $props['useNchan'] = true;
            $props['nowPlayingUri'] = '/api/live/nowplaying/' . urlencode($station->getShortName());
        } else {
            $props['useNchan'] = false;
            $props['nowPlayingUri'] = (string)$router->named(
                'api:nowplaying:index',
                ['station_id' => $station->getId()]
            );
        }

        // Render embedded player.
        if (!empty($embed)) {
            $pageClasses = [];
            $pageClasses[] = 'page-station-public-player-embed station-' . $station->getShortName();
            $pageClasses[] = ('social' === $embed) ? 'embed-social' : 'embed';

            return $request->getView()->renderVuePage(
                response: $response,
                component: 'Vue_PublicPlayer',
                id: 'station-nowplaying',
                layout: 'minimal',
                title: $station->getName(),
                layoutParams: [
                    'page_class' => implode(' ', $pageClasses),
                    'hide_footer' => true,
                ],
                props: $props,
            );
        }

        // Render full page player.
        $props['stationName'] = $station->getName();
        $props['enableRequests'] = $station->getEnableRequests();
        $props['downloadPlaylistUri'] = (string)$router->named(
            'public:playlist',
            ['station_id' => $station->getShortName(), 'format' => 'pls']
        );
        $props['requestListUri'] = (string)$router->named(
            'api:requests:list',
            ['station_id' => $station->getId()]
        );
        $props['customFields'] = $this->customFieldRepo->fetchArray();

        return $request->getView()->renderToResponse(
            $response,
            'frontend/public/index',
            [
                'station' => $station,
                'props' => $props,
                'defaultAlbumArt' => $defaultAlbumArt,
            ]
        );
    }
}
