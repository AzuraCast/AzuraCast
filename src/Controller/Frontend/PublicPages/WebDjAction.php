<?php

declare(strict_types=1);

namespace App\Controller\Frontend\PublicPages;

use App\Assets;
use App\Exception\StationNotFoundException;
use App\Exception\StationUnsupportedException;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Radio\Backend\Liquidsoap;
use Psr\Http\Message\ResponseInterface;

class WebDjAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        Assets $assets
    ): ResponseInterface {
        // Override system-wide iframe refusal
        $response = $response->withHeader('X-Frame-Options', '*');

        $station = $request->getStation();

        if (!$station->getEnablePublicPage()) {
            throw new StationNotFoundException();
        }

        if (!$station->getEnableStreamers()) {
            throw new StationUnsupportedException();
        }

        $backend = $request->getStationBackend();

        if (!($backend instanceof Liquidsoap)) {
            throw new StationUnsupportedException();
        }

        $router = $request->getRouter();

        $wss_url = (string)$backend->getWebStreamingUrl($station, $request->getRouter()->getBaseUrl());
        $wss_url = str_replace('wss://', '', $wss_url);

        $libUrls = [];
        $lib = $assets->getLibrary('Vue_PublicWebDJ');
        if (null !== $lib) {
            foreach (array_slice($lib['files']['js'], 0, -1) as $script) {
                $libUrls[] = (string)($router->getBaseUrl()->withPath($assets->getUrl($script['src'])));
            }
        }

        return $request->getView()->renderVuePage(
            response: $response,
            component: 'Vue_PublicWebDJ',
            id: 'web_dj',
            layout: 'minimal',
            title: __('Web DJ') . ' - ' . $station->getName(),
            layoutParams: [
                'page_class' => 'dj station-' . $station->getShortName(),
                'hide_footer' => true,
            ],
            props: [
                'stationName' => $station->getName(),
                'libUrls' => $libUrls,
                'baseUri' => $wss_url,
            ],
        );
    }
}
