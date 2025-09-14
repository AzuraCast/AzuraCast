<?php

declare(strict_types=1);

namespace App\Controller\Frontend\PublicPages;

use App\Controller\SingleActionInterface;
use App\Exception\NotFoundException;
use App\Http\Response;
use App\Http\ServerRequest;
use App\VueComponent\NowPlayingComponent;
use Psr\Http\Message\ResponseInterface;

final readonly class HistoryAction implements SingleActionInterface
{
    public function __construct(
        private NowPlayingComponent $nowPlayingComponent
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $station = $request->getStation();

        if (!$station->enable_public_page) {
            throw NotFoundException::station();
        }

        $view = $request->getView();

        // Add station public code.
        $view->fetch(
            'frontend/public/partials/station-custom',
            ['station' => $station]
        );

        return $view->renderVuePage(
            response: $response->withHeader('X-Frame-Options', '*'),
            component: 'Public/History',
            id: 'song-history',
            layout: 'minimal',
            title: __('History') . ' - ' . $station->name,
            layoutParams: [
                'page_class' => 'embed station-' . $station->short_name,
                'hide_footer' => true,
            ],
            props: $this->nowPlayingComponent->getProps($request),
        );
    }
}
