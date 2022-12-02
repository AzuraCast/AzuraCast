<?php

declare(strict_types=1);

namespace App\Controller\Frontend\PublicPages;

use App\Entity;
use App\Exception\StationNotFoundException;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class PlayerAction
{
    public function __construct(
        private readonly Entity\ApiGenerator\NowPlayingApiGenerator $npApiGenerator,
        private readonly Entity\Repository\CustomFieldRepository $customFieldRepo
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
            'nowPlayingUri' => $customization->useStaticNowPlaying()
                ? '/api/nowplaying_static/' . urlencode($station->getShortName()) . '.json'
                : $router->named('api:nowplaying:index', ['station_id' => $station->getShortName()]),
        ];

        /*
        if ($customization->useStaticNowPlaying() && $this->centrifugo->isSupported()) {
            $props['useSse'] = true;
            $props['sseUri'] = $this->centrifugo->getSseUrl($station);
        }
        */

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

        $props['downloadPlaylistUri'] = $router->named(
            'public:playlist',
            ['station_id' => $station->getShortName(), 'format' => 'pls']
        );

        // Auto-redirect requests from players to the playlist (PLS) download.
        $userAgent = strtolower($request->getHeaderLine('User-Agent'));
        $players = ['mpv', 'player', 'vlc', 'applecoremedia'];
        foreach ($players as $player) {
            if (str_contains($userAgent, $player)) {
                return $response->withRedirect($props['downloadPlaylistUri']);
            }
        }

        // Render full page player.
        $props['stationName'] = $station->getName();
        $props['enableRequests'] = $station->getEnableRequests();

        $props['requestListUri'] = $router->named(
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
                'nowPlayingArtUri' => $router->named(
                    routeName: 'api:nowplaying:art',
                    routeParams: ['station_id' => $station->getShortName(), 'timestamp' => time()],
                    absolute: true
                ),
            ]
        );
    }
}
