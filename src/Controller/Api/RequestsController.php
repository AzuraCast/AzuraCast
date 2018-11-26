<?php
namespace App\Controller\Api;

use Azura\Doctrine\Paginator;
use App\Utilities;
use App\ApiUtilities;
use Doctrine\ORM\EntityManager;
use App\Entity;
use App\Http\Request;
use App\Http\Response;
use Psr\Http\Message\ResponseInterface;

class RequestsController
{
    /** @var EntityManager */
    protected $em;

    /** @var ApiUtilities */
    protected $api_utils;

    /**
     * @param EntityManager $em
     * @param ApiUtilities $api_utils
     * @see \App\Provider\ApiProvider
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
     *       @OA\Items(ref="#/components/schemas/StationRequest")
     *     )
     *   ),
     *   @OA\Response(response=404, description="Station not found"),
     *   @OA\Response(response=403, description="Station does not support requests")
     * )
     */
    public function listAction(Request $request, Response $response, $station_id): ResponseInterface
    {
        $station = $request->getStation();

        // Verify that the station supports requests.
        $ba = $request->getStationBackend();
        if (!$ba::supportsRequests() || !$station->getEnableRequests()) {
            return $response->withJson('This station does not accept requests currently.', 403);
        }

        $qb = $this->em->createQueryBuilder();

        $qb->select('sm, s, spm, sp')
            ->from(Entity\StationMedia::class, 'sm')
            ->join('sm.song', 's')
            ->leftJoin('sm.playlist_items', 'spm')
            ->leftJoin('spm.playlist', 'sp')
            ->where('sm.station_id = :station_id')
            ->andWhere('sp.id IS NOT NULL')
            ->andWhere('sp.is_enabled = 1')
            ->andWhere('sp.include_in_requests = 1')
            ->setParameter('station_id', $station_id);

        if ($request->hasParam('sort')) {
            $sort_fields = [
                'song_title'    => 'sm.title',
                'song_artist'   => 'sm.artist',
                'song_album'    => 'sm.album',
            ];

            foreach($request->getParam('sort') as $sort_key => $sort_direction)
            {
                if (isset($sort_fields[$sort_key])) {
                    $qb->addOrderBy($sort_fields[$sort_key], $sort_direction);
                }
            }
        } else {
            $qb->orderBy('sm.artist', 'ASC')
                ->addOrderBy('sm.title', 'ASC');
        }

        $search_phrase = trim($request->getParam('searchPhrase'));
        if (!empty($search_phrase)) {
            $qb->andWhere('(sm.title LIKE :query OR sm.artist LIKE :query OR sm.album LIKE :query)')
                ->setParameter('query', '%'.$search_phrase.'%');
        }

        $paginator = new Paginator($qb);
        $paginator->setFromRequest($request);

        $is_bootgrid = $paginator->isFromBootgrid();
        $router = $request->getRouter();

        $paginator->setPostprocessor(function($media_row) use ($station_id, $is_bootgrid, $router) {
            /** @var Entity\StationMedia $media_row */
            $row = new Entity\Api\StationRequest;
            $row->song = $media_row->api($this->api_utils);
            $row->request_id = $media_row->getUniqueId();
            $row->request_url = (string)$router->named('api:requests:submit', [
                'station' => $station_id,
                'media_id' => $media_row->getUniqueId(),
            ]);

            $row->resolveUrls($router);

            if ($is_bootgrid) {
                return Utilities::flatten_array($row, '_');
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
     */
    public function submitAction(Request $request, Response $response, $station_id, $media_id): ResponseInterface
    {
        $station = $request->getStation();

        // Verify that the station supports requests.
        $ba = $request->getStationBackend();
        if (!$ba::supportsRequests() || !$station->getEnableRequests()) {
            return $response->withJson('This station does not accept requests currently.', 403);
        }

        try {
            /** @var Entity\Repository\StationRequestRepository $request_repo */
            $request_repo = $this->em->getRepository(Entity\StationRequest::class);
            $request_repo->submit($station, $media_id);

            return $response->withJson('Request submitted successfully.');
        } catch (\Azura\Exception $e) {
            return $response->withJson($e->getMessage(), 400);
        }
    }
}
