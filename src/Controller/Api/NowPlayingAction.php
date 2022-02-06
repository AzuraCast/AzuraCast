<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity;
use App\Http\Response;
use App\Http\RouterInterface;
use App\Http\ServerRequest;
use App\OpenApi;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NoResultException;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\SimpleCache\CacheInterface;

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
class NowPlayingAction
{
    public function __construct(
        protected EntityManagerInterface $em,
        protected Entity\Repository\StationRepository $stationRepo,
        protected CacheInterface $cache
    ) {
    }

    /**
     * @param ServerRequest $request
     * @param Response $response
     * @param int|string|null $station_id
     */
    public function __invoke(ServerRequest $request, Response $response, $station_id = null): ResponseInterface
    {
        $router = $request->getRouter();

        if (!empty($station_id)) {
            $np = $this->getForStation($station_id, $router);

            if (null !== $np) {
                return $response->withJson($np);
            }

            return $response->withStatus(404)
                ->withJson(Entity\Api\Error::notFound());
        }

        return $response->withJson(
            $this->getForAllStations(
                $router,
                $request->getAttribute('user') === null // If unauthenticated, hide non-public stations from full view.
            )
        );
    }

    protected function getForStation(
        string|int $station,
        RouterInterface $router
    ): ?Entity\Api\NowPlaying\NowPlaying {
        // Check cache first.
        $np = $this->cache->get('nowplaying.' . $station);

        if (!($np instanceof Entity\Api\NowPlaying\NowPlaying)) {
            // Pull from DB if possible.
            if (is_numeric($station)) {
                $dql = <<<'DQL'
                    SELECT s.nowplaying FROM App\Entity\Station s
                    WHERE s.id = :id
                DQL;
            } else {
                $dql = <<<'DQL'
                    SELECT s.nowplaying FROM App\Entity\Station s
                    WHERE s.short_name = :id
                DQL;
            }

            try {
                $npResult = $this->em->createQuery($dql)
                    ->setParameter('id', $station)
                    ->setMaxResults(1)
                    ->getSingleResult();

                $np = $npResult['nowplaying'] ?? null;
            } catch (NoResultException $e) {
                return null;
            }
        }

        if ($np instanceof Entity\Api\NowPlaying\NowPlaying) {
            $np->resolveUrls($router->getBaseUrl());
            $np->update();
            return $np;
        }

        return null;
    }

    protected function getForAllStations(
        RouterInterface $router,
        bool $publicOnly = false,
    ): array {
        if ($publicOnly) {
            $dql = <<<'DQL'
                SELECT s.nowplaying FROM App\Entity\Station s 
                WHERE s.is_enabled = 1 AND s.enable_public_page = 1
            DQL;
        } else {
            $dql = <<<'DQL'
                SELECT s.nowplaying FROM App\Entity\Station s 
                WHERE s.is_enabled = 1
            DQL;
        }

        $np = [];
        $baseUrl = $router->getBaseUrl();

        foreach ($this->em->createQuery($dql)->getArrayResult() as $row) {
            $npRow = $row['nowplaying'];
            if ($npRow instanceof Entity\Api\NowPlaying\NowPlaying) {
                $npRow->resolveUrls($baseUrl);
                $npRow->update();
                $np[] = $npRow;
            }
        }

        return $np;
    }
}
