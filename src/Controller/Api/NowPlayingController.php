<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Cache\NowPlayingCache;
use App\Entity\Api\Error;
use App\Entity\Api\NowPlaying\NowPlaying;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[
    OA\Get(
        path: '/nowplaying',
        description: "Returns a full summary of all stations' current state.",
        tags: ['Now Playing'],
        parameters: [],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/Api_NowPlaying')
                )
            ),
        ]
    ),
    OA\Get(
        path: '/nowplaying/{station_id}',
        description: "Returns a full summary of the specified station's current state.",
        tags: ['Now Playing'],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(ref: '#/components/schemas/Api_NowPlaying')
            ),
            new OA\Response(ref: OpenApi::REF_RESPONSE_NOT_FOUND, response: 404),
        ]
    )
]
final class NowPlayingController
{
    public function __construct(
        private readonly NowPlayingCache $nowPlayingCache
    ) {
    }

    public function getAction(
        ServerRequest $request,
        Response $response,
        ?string $station_id = null
    ): ResponseInterface {
        $router = $request->getRouter();

        if (!empty($station_id)) {
            $np = $this->nowPlayingCache->getForStation($station_id);

            if ($np instanceof NowPlaying) {
                $np->resolveUrls($router->getBaseUrl());
                $np->update();

                return $response->withJson($np);
            }

            return $response->withStatus(404)
                ->withJson(Error::notFound());
        }

        $baseUrl = $router->getBaseUrl();

        // If unauthenticated, hide non-public stations from full view.
        $np = $this->nowPlayingCache->getForAllStations(
            $request->getAttribute('user') === null
        );

        $np = array_map(
            function (NowPlaying $npRow) use ($baseUrl) {
                $npRow->resolveUrls($baseUrl);
                $npRow->update();
                return $npRow;
            },
            $np
        );

        return $response->withJson($np);
    }

    public function getArtAction(
        ServerRequest $request,
        Response $response,
        string $station_id,
        ?string $timestamp = null
    ): ResponseInterface {
        $np = $this->nowPlayingCache->getForStation($station_id);

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
