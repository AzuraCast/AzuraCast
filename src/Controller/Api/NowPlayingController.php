<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Cache\NowPlayingCache;
use App\Entity\Api\Error;
use App\Entity\Api\NowPlaying\NowPlaying;
use App\Exception\InvalidRequestAttribute;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[
    OA\Get(
        path: '/nowplaying',
        operationId: 'getAllNowPlaying',
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
        operationId: 'getStationNowPlaying',
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
        array $params
    ): ResponseInterface {
        /** @var string|null $stationId */
        $stationId = $params['station_id'] ?? null;

        $router = $request->getRouter();

        if (!empty($stationId)) {
            $np = $this->nowPlayingCache->getForStation($stationId);

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
        try {
            $user = $request->getUser();
        } catch (InvalidRequestAttribute) {
            $user = null;
        }

        $np = $this->nowPlayingCache->getForAllStations(null === $user);

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
        array $params
    ): ResponseInterface {
        /** @var string $stationId */
        $stationId = $params['station_id'];

        $np = $this->nowPlayingCache->getForStation($stationId);

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
