<?php
namespace App\Controller\Api\Stations;

use App\ApiUtilities;
use App\Entity;
use App\Exception;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Paginator\QueryPaginator;
use App\Utilities;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Annotations as OA;
use Psr\Http\Message\ResponseInterface;

class RequestsController
{
    protected EntityManagerInterface $em;

    protected Entity\Repository\StationRequestRepository $requestRepo;

    protected ApiUtilities $api_utils;

    public function __construct(
        EntityManagerInterface $em,
        Entity\Repository\StationRequestRepository $requestRepo,
        ApiUtilities $api_utils
    ) {
        $this->em = $em;
        $this->requestRepo = $requestRepo;
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
     * @param ServerRequest $request
     * @param Response $response
     *
     * @return ResponseInterface
     * @throws Exception
     * @throws Exception\InvalidRequestAttribute
     */
    public function listAction(ServerRequest $request, Response $response): ResponseInterface
    {
        $station = $request->getStation();

        // Verify that the station supports requests.
        $ba = $request->getStationBackend();
        if (!$ba::supportsRequests() || !$station->getEnableRequests()) {
            return $response->withStatus(403)
                ->withJson(new Entity\Api\Error(403, __('This station does not accept requests currently.')));
        }

        $qb = $this->em->createQueryBuilder();

        $qb->select('sm, spm, sp')
            ->from(Entity\StationMedia::class, 'sm')
            ->leftJoin('sm.playlists', 'spm')
            ->leftJoin('spm.playlist', 'sp')
            ->where('sm.station_id = :station_id')
            ->andWhere('sp.id IS NOT NULL')
            ->andWhere('sp.is_enabled = 1')
            ->andWhere('sp.include_in_requests = 1')
            ->setParameter('station_id', $station->getId());

        $params = $request->getQueryParams();

        if (!empty($params['sort'])) {
            $sort_fields = [
                'song_title' => 'sm.title',
                'song_artist' => 'sm.artist',
                'song_album' => 'sm.album',
            ];

            foreach ($params['sort'] as $sort_key => $sort_direction) {
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
                ->setParameter('query', '%' . $search_phrase . '%');
        }

        $paginator = new QueryPaginator($qb, $request);

        $is_bootgrid = $paginator->isFromBootgrid();
        $router = $request->getRouter();

        $paginator->setPostprocessor(function ($media_row) use ($station, $is_bootgrid, $router) {
            /** @var Entity\StationMedia $media_row */
            $row = new Entity\Api\StationRequest;
            $row->song = $media_row->api($this->api_utils);
            $row->request_id = $media_row->getUniqueId();
            $row->request_url = (string)$router->named('api:requests:submit', [
                'station_id' => $station->getId(),
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
     *         type="integer", format="int64"
     *     )
     *   ),
     *   @OA\Response(response=200, description="Success"),
     *   @OA\Response(response=404, description="Station not found"),
     *   @OA\Response(response=403, description="Station does not support requests")
     * )
     *
     * @param ServerRequest $request
     * @param Response $response
     * @param mixed $media_id
     *
     * @return ResponseInterface
     * @throws Exception\InvalidRequestAttribute
     */
    public function submitAction(ServerRequest $request, Response $response, $media_id): ResponseInterface
    {
        $station = $request->getStation();

        // Verify that the station supports requests.
        $ba = $request->getStationBackend();
        if (!$ba::supportsRequests() || !$station->getEnableRequests()) {
            return $response->withStatus(403)
                ->withJson(new Entity\Api\Error(403, __('This station does not accept requests currently.')));
        }

        try {
            $user = $request->getUser();
        } catch (Exception\InvalidRequestAttribute $e) {
            $user = null;
        }

        $isAuthenticated = ($user instanceof Entity\User);

        try {
            $this->requestRepo->submit($station, $media_id, $isAuthenticated, $request->getIp());

            return $response->withJson(new Entity\Api\Status(true, __('Request submitted successfully.')));
        } catch (Exception $e) {
            return $response->withStatus(400)
                ->withJson(new Entity\Api\Error(400, $e->getMessage()));
        }
    }
}
