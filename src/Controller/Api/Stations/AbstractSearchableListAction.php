<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations;

use App\Container\EntityManagerAwareTrait;
use App\Controller\SingleActionInterface;
use App\Entity\ApiGenerator\SongApiGenerator;
use App\Entity\StationMedia;
use App\Http\ServerRequest;
use App\Paginator;
use Psr\Cache\CacheItemPoolInterface;
use RuntimeException;

abstract class AbstractSearchableListAction implements SingleActionInterface
{
    use EntityManagerAwareTrait;

    public function __construct(
        protected readonly SongApiGenerator $songApiGenerator,
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
        if (empty($playlists)) {
            throw new RuntimeException('This station has no qualifying playlists for this feature.');
        }

        $station = $request->getStation();

        $queryParams = $request->getQueryParams();
        $searchPhrase = trim($queryParams['searchPhrase'] ?? '');

        $sortField = (string)($queryParams['sort'] ?? '');
        $sortDirection = strtolower($queryParams['sortOrder'] ?? 'asc');

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
