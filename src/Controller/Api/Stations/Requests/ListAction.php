<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Requests;

use App\Doctrine\Paginator\HydratingAdapter;
use App\Entity\Api\Error;
use App\Entity\Api\StationRequest;
use App\Entity\ApiGenerator\SongApiGenerator;
use App\Entity\StationMedia;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use App\Paginator;
use App\Service\Meilisearch;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[
    OA\Get(
        path: '/station/{station_id}/requests',
        operationId: 'getRequestableSongs',
        description: 'Return a list of requestable songs.',
        tags: ['Stations: Song Requests'],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/Api_StationRequest')
                )
            ),
            new OA\Response(ref: OpenApi::REF_RESPONSE_ACCESS_DENIED, response: 403),
            new OA\Response(ref: OpenApi::REF_RESPONSE_NOT_FOUND, response: 404),
            new OA\Response(ref: OpenApi::REF_RESPONSE_GENERIC_ERROR, response: 500),
        ]
    )
]
final class ListAction
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly SongApiGenerator $songApiGenerator,
        private readonly Meilisearch $meilisearch
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        string $station_id
    ): ResponseInterface {
        $station = $request->getStation();

        // Verify that the station supports on-demand streaming.
        if (!$station->getEnableRequests()) {
            return $response->withStatus(403)
                ->withJson(new Error(403, __('This station does not support requests.')));
        }

        $queryParams = $request->getQueryParams();
        $searchPhrase = trim($queryParams['searchPhrase'] ?? '');
        $sortField = (string)($queryParams['sort'] ?? '');
        $sortDirection = strtolower($queryParams['sortOrder'] ?? 'asc');

        if ($this->meilisearch->isSupported()) {
            $index = $this->meilisearch->getIndex($station->getMediaStorageLocation());

            $searchParams = [];
            if (!empty($sortField)) {
                $searchParams['sort'] = [$sortField . ':' . $sortDirection];
            }

            $paginatorAdapter = $index->getRequestableSearchPaginator(
                $station,
                $searchPhrase,
                $searchParams,
            );

            $hydrateCallback = function (iterable $results) {
                $ids = array_column([...$results], 'id');

                return $this->em->createQuery(
                    <<<'DQL'
                    SELECT sm
                    FROM App\Entity\StationMedia sm
                    WHERE sm.id IN (:ids)
                    ORDER BY FIELD(sm.id, :ids)
                DQL
                )->setParameter('ids', $ids)
                    ->toIterable();
            };

            $hydratingAdapter = new HydratingAdapter(
                $paginatorAdapter,
                $hydrateCallback(...)
            );

            $paginator = Paginator::fromAdapter($hydratingAdapter, $request);
        } else {
            $playlistsRaw = $this->em->createQuery(
                <<<'DQL'
                SELECT sp.id FROM App\Entity\StationPlaylist sp
                WHERE sp.station = :station
                AND sp.is_enabled = 1 AND sp.include_in_requests = 1
                DQL
            )->setParameter('station', $station)
                ->getArrayResult();

            $playlistIds = array_column($playlistsRaw, 'id');

            $qb = $this->em->createQueryBuilder();
            $qb->select('sm, spm, sp')
                ->from(StationMedia::class, 'sm')
                ->leftJoin('sm.playlists', 'spm')
                ->leftJoin('spm.playlist', 'sp')
                ->where('sm.storage_location = :storageLocation')
                ->andWhere('sp.id IN (:playlistIds)')
                ->setParameter('storageLocation', $station->getMediaStorageLocation())
                ->setParameter('playlistIds', $playlistIds);

            if (!empty($sortField)) {
                match ($sortField) {
                    'name', 'title' => $qb->addOrderBy('sm.title', $sortDirection),
                    'artist' => $qb->addOrderBy('sm.artist', $sortDirection),
                    'album' => $qb->addOrderBy('sm.album', $sortDirection),
                    'genre' => $qb->addOrderBy('sm.genre', $sortDirection),
                    default => null,
                };
            } else {
                $qb->orderBy('sm.artist', 'ASC')
                    ->addOrderBy('sm.title', 'ASC');
            }

            if (!empty($searchPhrase)) {
                $qb->andWhere('(sm.title LIKE :query OR sm.artist LIKE :query OR sm.album LIKE :query)')
                    ->setParameter('query', '%' . $searchPhrase . '%');
            }

            $paginator = Paginator::fromQueryBuilder($qb, $request);
        }

        $router = $request->getRouter();

        $paginator->setPostprocessor(
            function (StationMedia $media) use ($station, $router) {
                $row = new StationRequest();
                $row->song = ($this->songApiGenerator)($media, $station, $router->getBaseUrl());
                $row->request_id = $media->getUniqueId();
                $row->request_url = $router->named(
                    'api:requests:submit',
                    [
                        'station_id' => $station->getId(),
                        'media_id' => $media->getUniqueId(),
                    ]
                );

                $row->resolveUrls($router->getBaseUrl());

                return $row;
            }
        );

        return $paginator->write($response);
    }
}
