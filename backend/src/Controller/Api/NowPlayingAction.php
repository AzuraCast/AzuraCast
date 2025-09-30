<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Cache\NowPlayingCache;
use App\Controller\SingleActionInterface;
use App\Entity\Api\Error;
use App\Entity\Api\NowPlaying\NowPlaying;
use App\Exception\Http\InvalidRequestAttribute;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use Exception;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[
    OA\Get(
        path: '/nowplaying',
        operationId: 'getAllNowPlaying',
        description: "Returns a full summary of all stations' current state.",
        security: [],
        tags: [OpenApi::TAG_PUBLIC_NOW_PLAYING],
        parameters: [],
        responses: [
            new OpenApi\Response\Success(
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: NowPlaying::class)
                )
            ),
        ]
    ),
    OA\Get(
        path: '/nowplaying/{station_id}',
        operationId: 'getStationNowPlaying',
        description: "Returns a full summary of the specified station's current state.",
        security: [],
        tags: [OpenApi::TAG_PUBLIC_NOW_PLAYING],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
        ],
        responses: [
            new OpenApi\Response\Success(
                content: new OA\JsonContent(ref: NowPlaying::class)
            ),
            new OpenApi\Response\NotFound(),
        ]
    )
]
final readonly class NowPlayingAction implements SingleActionInterface
{
    public function __construct(
        private NowPlayingCache $nowPlayingCache
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        try {
            $station = $request->getStation();
        } catch (InvalidRequestAttribute) {
            $station = null;
        }

        if (null !== $station) {
            $np = $this->nowPlayingCache->getForStation($station);

            $accessAllowed = true;
            if (!$station->enable_public_page) {
                try {
                    $request->getUser();
                } catch (Exception) {
                    $accessAllowed = false;
                }
            }

            if ($np instanceof NowPlaying && $accessAllowed) {
                $np->update();

                return $response->withJson($np);
            }

            return $response->withStatus(404)
                ->withJson(Error::notFound());
        }

        $np = $this->nowPlayingCache->getForAllStations(true);
        $np = array_map(
            function (NowPlaying $npRow) {
                $npRow->update();
                return $npRow;
            },
            $np
        );

        return $response->withJson($np);
    }
}
