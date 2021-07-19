<?php

declare(strict_types=1);

namespace App\Controller\Frontend\PublicPages;

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

        $wss_url = (string)$backend->getWebStreamingUrl($station, $request->getRouter()->getBaseUrl());
        $wss_url = str_replace('wss://', '', $wss_url);

        return $request->getView()->renderToResponse($response, 'frontend/public/dj', [
            'station' => $station,
            'base_uri' => $wss_url,
        ]);
    }
}
