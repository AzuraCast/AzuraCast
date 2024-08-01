<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Cache\NowPlayingCache;
use App\Controller\SingleActionInterface;
use App\Entity\Api\Error;
use App\Entity\Api\NowPlaying\NowPlaying;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class NowPlayingArtAction implements SingleActionInterface
{
    public function __construct(
        private readonly NowPlayingCache $nowPlayingCache
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $station = $request->getStation();

        $np = $this->nowPlayingCache->getForStation($station);

        if ($np instanceof NowPlaying) {
            $np->resolveUrls($request->getRouter()->getBaseUrl());
            $np->update();

            $currentArt = $np->now_playing?->song?->art;
            if (null !== $currentArt) {
                return $response->withRedirect((string)$currentArt, 302);
            }
        }

        return $response->withStatus(404)
            ->withJson(Error::notFound());
    }
}
