<?php

declare(strict_types=1);

namespace App\Controller\Frontend\PublicPages;

use App\Controller\Frontend\PublicPages\Traits\IsEmbeddable;
use App\Controller\SingleActionInterface;
use App\Entity\Repository\CustomFieldRepository;
use App\Exception\NotFoundException;
use App\Http\Response;
use App\Http\ServerRequest;
use App\VueComponent\NowPlayingComponent;
use Psr\Http\Message\ResponseInterface;

final class PlayerAction implements SingleActionInterface
{
    use IsEmbeddable;

    public function __construct(
        private readonly CustomFieldRepository $customFieldRepo,
        private readonly NowPlayingComponent $nowPlayingComponent
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $embed = $this->isEmbedded($request, $params);

        $response = $response
            ->withHeader('X-Frame-Options', '*')
            ->withHeader('X-Robots-Tag', 'index, nofollow');

        $station = $request->getStation();

        if (!$station->enable_public_page) {
            throw NotFoundException::station();
        }

        // Build Vue props.
        $router = $request->getRouter();

        $playerProps = $this->nowPlayingComponent->getProps($request);

        // Render embedded player.
        if ($embed) {
            $pageClasses = [];
            $pageClasses[] = 'page-station-public-player-embed station-' . $station->short_name;
            $pageClasses[] = ('social' === ($params['embed'] ?? null)) ? 'embed-social' : 'embed';

            $view = $request->getView();

            // Add station public code.
            $view->fetch(
                'frontend/public/partials/station-custom',
                ['station' => $station]
            );

            return $view->renderVuePage(
                response: $response,
                component: 'Public/Player',
                id: 'station-nowplaying',
                layout: 'minimal',
                title: $station->name,
                layoutParams: [
                    'page_class' => implode(' ', $pageClasses),
                    'hide_footer' => true,
                ],
                props: $playerProps,
            );
        }

        $props = [
            'stationName' => $station->name,
            'enableRequests' => $station->enable_requests,
            'downloadPlaylistUri' => $router->named(
                routeName: 'public:playlist',
                routeParams: ['station_id' => $station->short_name, 'format' => 'pls']
            ),
            'requests' => [
                'requestListUri' => $router->named(
                    routeName: 'api:requests:list',
                    routeParams: ['station_id' => $station->id]
                ),
                'showAlbumArt' => $playerProps['showAlbumArt'],
                'customFields' => $this->customFieldRepo->fetchArray(),
            ],
            'player' => $playerProps,
        ];

        // Auto-redirect requests from players to the playlist (PLS) download.
        $userAgent = strtolower($request->getHeaderLine('User-Agent'));
        $players = ['mpv', 'player', 'vlc', 'applecoremedia'];

        if (array_any($players, fn($player) => str_contains($userAgent, $player))) {
            return $response->withRedirect($props['downloadPlaylistUri']);
        }

        // Render full page player.
        return $request->getView()->renderToResponse(
            $response,
            'frontend/public/index',
            [
                'station' => $station,
                'props' => $props,
                'nowPlayingArtUri' => $router->named(
                    routeName: 'api:nowplaying:art',
                    routeParams: ['station_id' => $station->short_name, 'timestamp' => time()],
                    absolute: true
                ),
            ]
        );
    }
}
