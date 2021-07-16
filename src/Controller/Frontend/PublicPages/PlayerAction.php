<?php

declare(strict_types=1);

namespace App\Controller\Frontend\PublicPages;

use App\Entity;
use App\Exception\StationNotFoundException;
use App\Http\Response;
use App\Http\Router;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

class PlayerAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        Entity\ApiGenerator\NowPlayingApiGenerator $npApiGenerator,
        Entity\Repository\CustomFieldRepository $customFieldRepo,
        Entity\Repository\StationRepository $stationRepo,
        ?string $embed = null
    ): ResponseInterface {
        // Override system-wide iframe refusal
        $response = $response
            ->withHeader('X-Frame-Options', '*')
            ->withHeader('X-Robots-Tag', 'index, nofollow');

        $station = $request->getStation();

        if (!$station->getEnablePublicPage()) {
            throw new StationNotFoundException();
        }

        $np = $npApiGenerator->currentOrEmpty($station);
        $np->resolveUrls($request->getRouter()->getBaseUrl());

        $defaultAlbumArtUri = $stationRepo->getDefaultAlbumArtUrl($station);
        $baseUrl = $request->getRouter()->getBaseUrl();
        $defaultAlbumArt = Router::resolveUri($baseUrl, $defaultAlbumArtUri, true);

        $autoplay = !empty($request->getQueryParam('autoplay'));

        $templateName = (!empty($embed))
            ? 'frontend/public/embed'
            : 'frontend/public/index';

        return $request->getView()->renderToResponse(
            $response,
            $templateName,
            [
                'isSocial' => ('social' === $embed),
                'autoplay' => $autoplay,
                'station' => $station,
                'defaultAlbumArt' => $defaultAlbumArt,
                'nowplaying' => $np,
                'customFields' => $customFieldRepo->fetchArray(),
            ]
        );
    }
}
