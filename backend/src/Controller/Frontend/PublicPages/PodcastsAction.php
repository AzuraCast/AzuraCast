<?php

declare(strict_types=1);

namespace App\Controller\Frontend\PublicPages;

use App\Controller\Frontend\PublicPages\Traits\IsEmbeddable;
use App\Controller\SingleActionInterface;
use App\Exception\NotFoundException;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Utilities\Types;
use Psr\Http\Message\ResponseInterface;

final class PodcastsAction implements SingleActionInterface
{
    use IsEmbeddable;

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $station = $request->getStation();

        if (!$station->getEnablePublicPage()) {
            throw NotFoundException::station();
        }

        $isEmbedded = $this->isEmbedded($request, $params);

        $pageClass = 'podcasts station-' . $station->getShortName();
        if ($isEmbedded) {
            $pageClass .= ' embed';
        }

        $groupLayout = Types::string($request->getQueryParam('layout'), 'table', true);

        $router = $request->getRouter();
        $view = $request->getView();

        // Add station public code.
        $view->fetch(
            'frontend/public/partials/station-custom',
            ['station' => $station]
        );

        return $view->renderVuePage(
            response: $response
                ->withHeader('X-Frame-Options', '*')
                ->withHeader('X-Robots-Tag', 'index, nofollow'),
            component: 'Public/Podcasts',
            id: 'podcast',
            layout: 'minimal',
            title: 'Podcasts - ' . $station->getName(),
            layoutParams: [
                'page_class' => $pageClass,
                'hide_footer' => $isEmbedded,
            ],
            props: [
                'stationId' => $station->getIdRequired(),
                'stationName' => $station->getName(),
                'stationTz' => $station->getTimezone(),
                'baseUrl' => $router->fromHere('public:index'),
                'groupLayout' => $groupLayout,
            ],
        );
    }
}
