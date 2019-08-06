<?php
namespace App\Controller\Api\Stations;

use App\ApiUtilities;
use App\Entity;
use App\Http\RequestHelper;
use App\Http\ResponseHelper;
use App\Utilities;
use Azura\Doctrine\Paginator;
use Doctrine\ORM\EntityManager;
use OpenApi\Annotations as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class RequestsController
{
    /** @var EntityManager */
    protected $em;

    /** @var ApiUtilities */
    protected $api_utils;

    /**
     * @param EntityManager $em
     * @param ApiUtilities $api_utils
     */
    public function __construct(EntityManager $em, ApiUtilities $api_utils)
    {
        $this->em = $em;
        $this->api_utils = $api_utils;
    }

    /**
     * @OA\Get(path="/station/{station_id}/requests",
     *   tags={"Stations: Song Requests"},
     *   description="Return a list of requestable songs.",
     *   @OA\Parameter(ref="#/components/parameters/station_id_required"),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\Schema(
     *       type="array",
     *       @OA\Items(ref="#/components/schemas/Api_StationRequest")
     *     )
     *   ),
     *   @OA\Response(response=404, description="Station not found"),
     *   @OA\Response(response=403, description="Station does not support requests")
     * )
     *
     * @inheritDoc
     */
    public function listAction(ServerRequestInterface $request, ResponseInterface $response, $station_id): ResponseInterface
    {
        $station = RequestHelper::getStation($request);

        // Verify that the station supports requests.
        $ba = RequestHelper::getStationBackend($request);
        if (!$ba::supportsRequests() || !$station->getEnableRequests()) {
            return ResponseHelper::withJson(
                $response->withStatus(403),
                new Entity\Api\Error(403, 'This station does not accept requests currently.')
            );
        }

        $qb = $this->em->createQueryBuilder();

        $qb->select('sm, s, spm, sp')
            ->from(Entity\StationMedia::class, 'sm')
            ->join('sm.song', 's')
            ->leftJoin('sm.playlists', 'spm')
            ->leftJoin('spm.playlist', 'sp')
            ->where('sm.station_id = :station_id')
            ->andWhere('sp.id IS NOT NULL')
            ->andWhere('sp.is_enabled = 1')
            ->andWhere('sp.include_in_requests = 1')
            ->setParameter('station_id', $station_id);

        $params = $request->getQueryParams();

        if (!empty($params['sort'])) {
            $sort_fields = [
                'song_title'    => 'sm.title',
                'song_artist'   => 'sm.artist',
                'song_album'    => 'sm.album',
            ];

            foreach($params['sort'] as $sort_key => $sort_direction)
            {
                if (isset($sort_fields[$sort_key])) {
                    $qb->addOrderBy($sort_fields[$sort_key], $sort_direction);
                }
            }
        } else {
            $qb->orderBy('sm.artist', 'ASC')
                ->addOrderBy('sm.title', 'ASC');
        }

        $search_phrase = trim($params['searchPhrase']);
        if (!empty($search_phrase)) {
            $qb->andWhere('(sm.title LIKE :query OR sm.artist LIKE :query OR sm.album LIKE :query)')
                ->setParameter('query', '%'.$search_phrase.'%');
        }

        $paginator = new Paginator($qb);
        $paginator->setFromRequest($request);

        $is_bootgrid = $paginator->isFromBootgrid();
        $router = RequestHelper::getRouter($request);

        $paginator->setPostprocessor(function($media_row) use ($station_id, $is_bootgrid, $router) {
            /** @var Entity\StationMedia $media_row */
            $row = new Entity\Api\StationRequest;
            $row->song = $media_row->api($this->api_utils);
            $row->request_id = $media_row->getUniqueId();
            $row->request_url = (string)$router->named('api:requests:submit', [
                'station' => $station_id,
                'media_id' => $media_row->getUniqueId(),
            ]);

            $row->resolveUrls($router->getBaseUrl());

            if ($is_bootgrid) {
                return Utilities::flattenArray($row, '_');
            }

            return $row;
        });

        return $paginator->write($response);
    }

    /**
     * @OA\Post(path="/station/{station_id}/request/{request_id}",
     *   tags={"Stations: Song Requests"},
     *   description="Submit a song request.",
     *   @OA\Parameter(ref="#/components/parameters/station_id_required"),
     *   @OA\Parameter(
     *     name="request_id",
     *     description="The requestable song ID",
     *     in="path",
     *     required=true,
     *     @OA\Schema(
     *         type="int64"
     *     )
     *   ),
     *   @OA\Response(response=200, description="Success"),
     *   @OA\Response(response=404, description="Station not found"),
     *   @OA\Response(response=403, description="Station does not support requests")
     * )
     *
     * @inheritDoc
     */
    public function submitAction(ServerRequestInterface $request, ResponseInterface $response, $station_id, $media_id): ResponseInterface
    {
        $station = RequestHelper::getStation($request);

        // Verify that the station supports requests.
        $ba = RequestHelper::getStationBackend($request);
        if (!$ba::supportsRequests() || !$station->getEnableRequests()) {
            return ResponseHelper::withJson(
                $response->withStatus(403),
                new Entity\Api\Error(403, 'This station does not accept requests currently.')
            );
        }

        $is_authenticated = !empty($request->getAttribute(RequestHelper::ATTR_USER));

        try {
            /** @var Entity\Repository\StationRequestRepository $request_repo */
            $request_repo = $this->em->getRepository(Entity\StationRequest::class);
            $request_repo->submit($station, $media_id, $is_authenticated);

            return ResponseHelper::withJson(
                $response,
                new Entity\Api\Status(true, 'Request submitted successfully.')
            );
        } catch (\Azura\Exception $e) {
            return ResponseHelper::withJson(
                $response->withStatus(400),
                new Entity\Api\Error(400, $e->getMessage())
            );
        }
    }
}
