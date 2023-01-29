<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\OnDemand;

use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Paginator;
use App\Service\Meilisearch;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface;

final readonly class ListAction
{
    public function __construct(
        private EntityManagerInterface $em,
        private Entity\ApiGenerator\SongApiGenerator $songApiGenerator,
        private Meilisearch $meilisearch
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        string $station_id
    ): ResponseInterface {
        $station = $request->getStation();

        // Verify that the station supports on-demand streaming.
        if (!$station->getEnableOnDemand()) {
            return $response->withStatus(403)
                ->withJson(new Entity\Api\Error(403, __('This station does not support on-demand streaming.')));
        }

        if (!$this->meilisearch->isSupported()) {
            return $response->withStatus(403)
                ->withJson(new Entity\Api\Error(403, __('This feature is not supported on this installation.')));
        }

        $index = $this->meilisearch->getIndex($station->getMediaStorageLocation());

        $queryParams = $request->getQueryParams();
        $searchPhrase = trim($queryParams['searchPhrase'] ?? '');

        $searchParams = [];
        if (!empty($queryParams['sort'])) {
            $sortField = (string)$queryParams['sort'];
            $sortDirection = strtolower($queryParams['sortOrder'] ?? 'asc');
            $searchParams['sort'] = [$sortField . ':' . $sortDirection];
        }

        $hydrateCallback = function (array $results) {
            $ids = array_column($results, 'id');

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

        $paginatorAdapter = $index->getOnDemandSearchPaginator(
            $station,
            $hydrateCallback,
            $searchPhrase,
            $searchParams,
        );

        $paginator = Paginator::fromAdapter($paginatorAdapter, $request);

        $router = $request->getRouter();

        $paginator->setPostprocessor(
            function (Entity\StationMedia $media) use ($station, $router) {
                $row = new Entity\Api\StationOnDemand();

                $row->track_id = $media->getUniqueId();
                $row->media = ($this->songApiGenerator)(
                    song: $media,
                    station: $station
                );

                $row->download_url = $router->named(
                    'api:stations:ondemand:download',
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
