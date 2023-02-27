<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations;

use App\Doctrine\Paginator\HydratingAdapter;
use App\Entity\ApiGenerator\SongApiGenerator;
use App\Entity\StationMedia;
use App\Http\ServerRequest;
use App\Paginator;
use App\Service\Meilisearch;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\CacheItemPoolInterface;

abstract class AbstractSearchableListAction
{
    public function __construct(
        protected readonly EntityManagerInterface $em,
        protected readonly SongApiGenerator $songApiGenerator,
        protected readonly Meilisearch $meilisearch,
        protected readonly CacheItemPoolInterface $psr6Cache,
    ) {
    }

    /**
     * @param int[] $playlists
     * @return Paginator<int, array>
     */
    protected function getPaginator(
        ServerRequest $request,
        array $playlists
    ): Paginator {
        $station = $request->getStation();

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

            $searchParams['filter'] = [
                'station_' . $station->getIdRequired() . '_playlists IN [' . implode(', ', $playlists) . ']',
            ];

            $paginatorAdapter = $index->getSearchPaginator(
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

            $hydrateAdapter = new HydratingAdapter(
                $paginatorAdapter,
                $hydrateCallback(...)
            );

            return Paginator::fromAdapter($hydrateAdapter, $request);
        }

        $qb = $this->em->createQueryBuilder();
        $qb->select('sm, spm, sp')
            ->from(StationMedia::class, 'sm')
            ->leftJoin('sm.playlists', 'spm')
            ->leftJoin('spm.playlist', 'sp')
            ->where('sm.storage_location = :storageLocation')
            ->andWhere('sp.id IN (:playlistIds)')
            ->setParameter('storageLocation', $station->getMediaStorageLocation())
            ->setParameter('playlistIds', $playlists);

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

        return Paginator::fromQueryBuilder($qb, $request);
    }
}
