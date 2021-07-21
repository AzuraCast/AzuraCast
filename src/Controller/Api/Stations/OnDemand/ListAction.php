<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\OnDemand;

use App\Doctrine\ReadOnlyBatchIteratorAggregate;
use App\Entity;
use App\Http\Response;
use App\Http\RouterInterface;
use App\Http\ServerRequest;
use App\Paginator;
use App\Utilities;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Cache\CacheItem;
use Symfony\Contracts\Cache\CacheInterface;

class ListAction
{
    public function __construct(
        protected EntityManagerInterface $em,
        protected Entity\Repository\CustomFieldRepository $customFieldRepo,
        protected Entity\ApiGenerator\SongApiGenerator $songApiGenerator
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        CacheInterface $cache
    ): ResponseInterface {
        $station = $request->getStation();

        // Verify that the station supports on-demand streaming.
        if (!$station->getEnableOnDemand()) {
            return $response->withStatus(403)
                ->withJson(new Entity\Api\Error(403, __('This station does not support on-demand streaming.')));
        }

        $cacheKey = 'ondemand_' . $station->getId();
        $trackList = $cache->get(
            $cacheKey,
            function (CacheItem $item) use ($station, $request) {
                $item->expiresAfter(300);
                return $this->buildTrackList($station, $request->getRouter());
            }
        );

        $trackList = new ArrayCollection($trackList);

        $params = $request->getQueryParams();

        $searchPhrase = trim($params['searchPhrase'] ?? '');
        if (!empty($searchPhrase)) {
            $searchFields = [
                'media_title',
                'media_artist',
                'media_album',
                'playlist',
            ];

            foreach (array_keys($this->customFieldRepo->getFieldIds()) as $customField) {
                $searchFields[] = 'media_custom_fields_' . $customField;
            }

            $trackList = $trackList->filter(
                function ($row) use ($searchFields, $searchPhrase) {
                    foreach ($searchFields as $searchField) {
                        if (false !== stripos($row[$searchField], $searchPhrase)) {
                            return true;
                        }
                    }

                    return false;
                }
            );
        }

        if (!empty($params['sort'])) {
            $sortField = $params['sort'];
            $sortDirection = $params['sortOrder'] ?? Criteria::ASC;

            $criteria = new Criteria();
            $criteria->orderBy([$sortField => $sortDirection]);

            $trackList = $trackList->matching($criteria);
        }

        return Paginator::fromCollection($trackList, $request)->write($response);
    }

    /**
     * @return mixed[]
     */
    protected function buildTrackList(Entity\Station $station, RouterInterface $router): array
    {
        $list = [];

        $playlists = $this->em->createQuery(
            <<<'DQL'
                SELECT sp FROM App\Entity\StationPlaylist sp
                WHERE sp.station = :station
                AND sp.id IS NOT NULL
                AND sp.is_enabled = 1
                AND sp.include_in_on_demand = 1
            DQL
        )->setParameter('station', $station)
            ->getArrayResult();

        foreach ($playlists as $playlist) {
            $query = $this->em->createQuery(
                <<<'DQL'
                    SELECT sm FROM App\Entity\StationMedia sm
                    WHERE sm.id IN (
                        SELECT spm.media_id
                        FROM App\Entity\StationPlaylistMedia spm
                        WHERE spm.playlist_id = :playlist_id
                    )
                    ORDER BY sm.artist ASC, sm.title ASC
                DQL
            )->setParameter('playlist_id', $playlist['id']);

            foreach (ReadOnlyBatchIteratorAggregate::fromQuery($query, 50) as $media) {
                /** @var Entity\StationMedia $media */
                $row = new Entity\Api\StationOnDemand();

                $row->track_id = $media->getUniqueId();
                $row->media = ($this->songApiGenerator)($media, $station);
                $row->playlist = $playlist['name'];
                $row->download_url = (string)$router->named(
                    'api:stations:ondemand:download',
                    [
                        'station_id' => $station->getId(),
                        'media_id' => $media->getUniqueId(),
                    ]
                );

                $row->resolveUrls($router->getBaseUrl());

                $list[] = Utilities\Arrays::flattenArray($row, '_');
            }
        }

        return $list;
    }
}
