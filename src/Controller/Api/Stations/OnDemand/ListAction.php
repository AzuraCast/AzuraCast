<?php

namespace App\Controller\Api\Stations\OnDemand;

use App\Entity;
use App\Http\Response;
use App\Http\RouterInterface;
use App\Http\ServerRequest;
use App\Paginator\ArrayPaginator;
use App\Utilities;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use DoctrineBatchUtils\BatchProcessing\SimpleBatchIteratorAggregate;
use Psr\Http\Message\ResponseInterface;
use Psr\SimpleCache\CacheInterface;

class ListAction
{
    protected EntityManagerInterface $em;

    protected Entity\Repository\CustomFieldRepository $customFieldRepo;

    protected Entity\ApiGenerator\SongApiGenerator $songApiGenerator;

    public function __construct(
        EntityManagerInterface $em,
        Entity\Repository\CustomFieldRepository $customFieldRepo,
        Entity\ApiGenerator\SongApiGenerator $songApiGenerator
    ) {
        $this->em = $em;
        $this->customFieldRepo = $customFieldRepo;
        $this->songApiGenerator = $songApiGenerator;
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
        if ($cache->has($cacheKey)) {
            $trackList = $cache->get($cacheKey, []);
        } else {
            $trackList = $this->buildTrackList($station, $request->getRouter());
            $cache->set($cacheKey, $trackList, 300);
        }

        $trackList = new ArrayCollection($trackList);

        $params = $request->getQueryParams();

        $searchPhrase = trim($params['searchPhrase']);
        if (!empty($searchPhrase)) {
            $searchFields = [
                'media_title',
                'media_artist',
                'media_album',
                'playlist',
            ];

            $customFields = array_keys($this->customFieldRepo->getFieldIds());
            foreach ($customFields as $customField) {
                $searchFields[] = 'media_custom_fields_' . $customField;
            }

            $trackList = $trackList->filter(function ($row) use ($searchFields, $searchPhrase) {
                foreach ($searchFields as $searchField) {
                    if (false !== stripos($row[$searchField], $searchPhrase)) {
                        return true;
                    }
                }

                return false;
            });
        }

        if (!empty($params['sort'])) {
            $sortField = $params['sort'];
            $sortDirection = $params['sortOrder'] ?? Criteria::ASC;

            $criteria = new Criteria();
            $criteria->orderBy([$sortField => $sortDirection]);

            $trackList = $trackList->matching($criteria);
        }

        $paginator = new ArrayPaginator($trackList, $request);
        return $paginator->write($response);
    }

    /**
     * @return mixed[]
     */
    protected function buildTrackList(Entity\Station $station, RouterInterface $router): array
    {
        $list = [];

        $playlists = $this->em->createQuery(/** @lang DQL */ '
            SELECT sp FROM App\Entity\StationPlaylist sp
            WHERE sp.station = :station
            AND sp.id IS NOT NULL
            AND sp.is_enabled = 1
            AND sp.include_in_on_demand = 1')
            ->setParameter('station', $station)
            ->getArrayResult();

        foreach ($playlists as $playlist) {
            $query = $this->em->createQuery(/** @lang DQL */ '
                SELECT sm FROM App\Entity\StationMedia sm
                WHERE sm.id IN (
                    SELECT spm.media_id
                    FROM App\Entity\StationPlaylistMedia spm
                    WHERE spm.playlist_id = :playlist_id
                )
                ORDER BY sm.artist ASC, sm.title ASC')
                ->setParameter('playlist_id', $playlist['id']);

            $iterator = SimpleBatchIteratorAggregate::fromQuery($query, 50);

            foreach ($iterator as $media) {
                /** @var Entity\StationMedia $media */
                $row = new Entity\Api\StationOnDemand();

                $row->track_id = $media->getUniqueId();
                $row->media = ($this->songApiGenerator)($media, $station);
                $row->playlist = $playlist['name'];
                $row->download_url = (string)$router->named('api:stations:ondemand:download', [
                    'station_id' => $station->getId(),
                    'media_id' => $media->getUniqueId(),
                ]);

                $row->resolveUrls($router->getBaseUrl());

                $list[] = Utilities::flattenArray($row, '_');
            }
        }

        return $list;
    }
}
