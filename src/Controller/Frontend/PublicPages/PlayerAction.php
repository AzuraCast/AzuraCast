<?php

namespace App\Controller\Frontend\PublicPages;

use App\Entity;
use App\Exception\StationNotFoundException;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

class PlayerAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        Entity\ApiGenerator\NowPlayingApiGenerator $npApiGenerator,
        Entity\Repository\CustomFieldRepository $customFieldRepo,
        bool $embed = false
    ): ResponseInterface {
        // Override system-wide iframe refusal
        $response = $response->withHeader('X-Frame-Options', '*');

        $station = $request->getStation();

        if (!$station->getEnablePublicPage()) {
            throw new StationNotFoundException();
        }

        $np = $npApiGenerator->currentOrEmpty($station);
        $np->resolveUrls($request->getRouter()->getBaseUrl());

        $templateName = ($embed)
            ? 'frontend/public/embed'
            : 'frontend/public/index';

        return $request->getView()->renderToResponse(
            $response,
            $templateName,
            [
                'station' => $station,
                'nowplaying' => $np,
                'customFields' => $customFieldRepo->fetchArray(),
            ]
        );
    }
}
