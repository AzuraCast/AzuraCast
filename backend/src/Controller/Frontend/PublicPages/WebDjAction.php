<?php

declare(strict_types=1);

namespace App\Controller\Frontend\PublicPages;

use App\Controller\SingleActionInterface;
use App\Enums\StationFeatures;
use App\Exception\NotFoundException;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Radio\Adapters;
use League\Plates\Template\Template;
use Psr\Http\Message\ResponseInterface;

final class WebDjAction implements SingleActionInterface
{
    public function __construct(
        private readonly Adapters $adapters,
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $station = $request->getStation();

        if (!$station->getEnablePublicPage()) {
            throw NotFoundException::station();
        }

        StationFeatures::Streamers->assertSupportedForStation($station);

        $backend = $this->adapters->requireBackendAdapter($station);

        $wssUrl = (string)$backend->getWebStreamingUrl($station, $request->getRouter()->getBaseUrl());

        $view = $request->getView();

        // Add station public code.
        $view->fetch(
            'frontend/public/partials/station-custom',
            ['station' => $station]
        );

        $view->getSections()->set(
            'bodyjs',
            <<<'HTML'
                <script src="/static/js/taglib.js"></script>
            HTML,
            Template::SECTION_MODE_APPEND
        );

        return $view->renderVuePage(
            response: $response
                ->withHeader('X-Frame-Options', '*')
                ->withHeader('X-Robots-Tag', 'index, nofollow'),
            component: 'Public/WebDJ',
            id: 'webdj',
            layout: 'minimal',
            title: __('Web DJ') . ' - ' . $station->getName(),
            layoutParams: [
                'page_class' => 'dj station-' . $station->getShortName(),
            ],
            props: [
                'baseUri' => $wssUrl,
                'stationName' => $station->getName(),
            ],
        );
    }
}
