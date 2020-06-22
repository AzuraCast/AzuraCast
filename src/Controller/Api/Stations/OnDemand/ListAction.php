<?php
namespace App\Controller\Api\Stations\OnDemand;

use App\ApiUtilities;
use App\Doctrine\Paginator;
use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Utilities;
use Doctrine\ORM\EntityManager;
use Psr\Http\Message\ResponseInterface;

class ListAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        EntityManager $em,
        ApiUtilities $apiUtils
    ): ResponseInterface {
        $station = $request->getStation();

        // Verify that the station supports on-demand streaming.
        if (!$station->getEnableOnDemand()) {
            return $response->withStatus(403)
                ->withJson(new Entity\Api\Error(403, __('This station does not support on-demand streaming.')));
        }

        $qb = $em->createQueryBuilder();

        $qb->select('sm, s, spm, sp')
            ->from(Entity\StationMedia::class, 'sm')
            ->join('sm.song', 's')
            ->leftJoin('sm.playlists', 'spm')
            ->leftJoin('spm.playlist', 'sp')
            ->where('sm.station_id = :station_id')
            ->andWhere('sp.id IS NOT NULL')
            ->andWhere('sp.is_enabled = 1')
            ->andWhere('sp.include_in_on_demand = 1')
            ->setParameter('station_id', $station->getId());

        $params = $request->getQueryParams();

        if (!empty($params['sort'])) {
            $sortFields = [
                'media_title' => 'sm.title',
                'media_artist' => 'sm.artist',
                'media_album' => 'sm.album',
            ];

            if (isset($sortFields[$params['sort']])) {
                $sortField = $sortFields[$params['sort']];
                $sortDirection = $params['sortOrder'] ?? 'ASC';
                $qb->addOrderBy($sortField, $sortDirection);
            }
        } else {
            $qb->orderBy('sm.artist', 'ASC')
                ->addOrderBy('sm.title', 'ASC');
        }

        $search_phrase = trim($params['searchPhrase']);
        if (!empty($search_phrase)) {
            $qb->andWhere('(sm.title LIKE :query OR sm.artist LIKE :query OR sm.album LIKE :query)')
                ->setParameter('query', '%' . $search_phrase . '%');
        }

        $paginator = new Paginator($qb);
        $paginator->setFromRequest($request);

        $isBootgrid = $paginator->isFromBootgrid();
        $router = $request->getRouter();

        $paginator->setPostprocessor(function ($media) use ($station, $isBootgrid, $router, $apiUtils) {
            /** @var Entity\StationMedia $media */
            $row = new Entity\Api\StationOnDemand();

            $row->track_id = $media->getUniqueId();
            $row->media = $media->api($apiUtils);
            $row->download_url = (string)$router->named('api:stations:ondemand:download', [
                'station_id' => $station->getId(),
                'media_id' => $media->getUniqueId(),
            ]);

            $row->resolveUrls($router->getBaseUrl());

            if ($isBootgrid) {
                return Utilities::flattenArray($row, '_');
            }

            return $row;
        });

        return $paginator->write($response);
    }
}
