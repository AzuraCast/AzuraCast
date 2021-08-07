<?php

declare(strict_types=1);

namespace App\Controller\Frontend\PublicPages;

use App\Entity;
use App\Exception\StationNotFoundException;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

class HistoryAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        Entity\ApiGenerator\NowPlayingApiGenerator $npApiGenerator
    ): ResponseInterface {
        // Override system-wide iframe refusal
        $response = $response->withHeader('X-Frame-Options', '*');

        $station = $request->getStation();

        if (!$station->getEnablePublicPage()) {
            throw new StationNotFoundException();
        }

        $np = $npApiGenerator->currentOrEmpty($station);
        $np->resolveUrls($request->getRouter()->getBaseUrl());

        return $request->getView()->renderToResponse(
            $response,
            'frontend/public/embedhistory',
            [
                'station' => $station,
                'nowplaying' => $np,
            ]
        );
    }
}
