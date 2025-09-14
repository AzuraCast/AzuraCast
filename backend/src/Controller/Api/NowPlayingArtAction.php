<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Cache\NowPlayingCache;
use App\Controller\SingleActionInterface;
use App\Entity\Api\Error;
use App\Entity\Api\NowPlaying\NowPlaying;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[
    OA\Get(
        path: '/nowplaying/{station_id}/art',
        operationId: 'getStationNowPlayingArt',
        summary: 'Always redirects to the current art for the given station.',
        security: [],
        tags: [OpenApi::TAG_PUBLIC_NOW_PLAYING],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
        ],
        responses: [
            new OpenApi\Response\Redirect(),
            new OpenApi\Response\NotFound(),
        ]
    )
]
final readonly class NowPlayingArtAction implements SingleActionInterface
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
        $station = $request->getStation();

        $np = $this->nowPlayingCache->getForStation($station);

        if ($np instanceof NowPlaying) {
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
