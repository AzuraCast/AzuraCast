<?php
namespace Controller\Api;

use App\Url;
use App\Utilities;
use AzuraCast\ApiUtilities;
use AzuraCast\Radio\Adapters;
use Doctrine\ORM\EntityManager;
use Entity;
use App\Http\Request;
use App\Http\Response;

class RequestsController
{
    /** @var EntityManager */
    protected $em;

    /** @var Adapters */
    protected $adapters;

    /** @var Url */
    protected $url;

    /** @var ApiUtilities */
    protected $api_utils;

    /**
     * RequestsController constructor.
     * @param EntityManager $em
     * @param Adapters $adapters
     * @param Url $url
     */
    public function __construct(EntityManager $em, Adapters $adapters, Url $url, ApiUtilities $api_utils)
    {
        $this->em = $em;
        $this->adapters = $adapters;
        $this->url = $url;
        $this->api_utils = $api_utils;
    }

    /**
     * @SWG\Get(path="/station/{station_id}/requests",
     *   tags={"Stations: Song Requests"},
     *   description="Return a list of requestable songs.",
     *   @SWG\Parameter(ref="#/parameters/station_id_required"),
     *   @SWG\Response(
     *     response=200,
     *     description="Success",
     *     @SWG\Schema(
     *       type="array",
     *       @SWG\Items(ref="#/definitions/StationRequest")
     *     )
     *   ),
     *   @SWG\Response(response=404, description="Station not found"),
     *   @SWG\Response(response=403, description="Station does not support requests")
     * )
     */
    public function listAction(Request $request, Response $response, $station_id): Response
    {
        /** @var Entity\Station $station */
        $station = $request->getAttribute('station');

        // Verify that the station supports requests.
        $ba = $this->adapters->getBackendAdapter($station);
        if (!$ba->supportsRequests() || !$station->getEnableRequests()) {
            return $response->withJson('This station does not accept requests currently.', 403);
        }

        $requestable_media = $this->em->createQuery('SELECT sm, s, sp 
            FROM Entity\StationMedia sm JOIN sm.song s LEFT JOIN sm.playlists sp
            WHERE sm.station_id = :station_id 
            AND sp.id IS NOT NULL
            AND sp.is_enabled = 1 
            AND sp.include_in_requests = 1')
            ->setParameter('station_id', $station_id)
            ->useResultCache(true, 60)
            ->execute();

        $result = [];

        foreach ($requestable_media as $media_row) {
            /** @var Entity\StationMedia $media_row */
            $row = new Entity\Api\StationRequest;
            $row->song = $media_row->api($this->api_utils);
            $row->request_id = (int)$media_row->getId();
            $row->request_url = (string)$this->url->named('api:requests:submit', [
                'station' => $station_id,
                'media_id' => $media_row->getUniqueId(),
            ]);
            $result[] = $row;
        }

        // Handle Bootgrid-style iteration through result
        if ($request->hasParam('current')) {
            $result = json_decode(json_encode($result), true);

            // Flatten the results array for bootgrid.
            foreach ($result as &$row) {
                foreach ($row['song'] as $song_key => $song_val) {
                    $row['song_' . $song_key] = $song_val;
                }
            }
            unset($row);

            // Example from bootgrid docs:
            // current=1&rowCount=10&sort[sender]=asc&searchPhrase=&id=b0df282a-0d67-40e5-8558-c9e93b7befed

            // Apply sorting, limiting and searching.
            $search_phrase = trim($request->getParam('searchPhrase'));

            if (!empty($search_phrase)) {
                $result = array_filter($result, function ($row) use ($search_phrase) {
                    $search_fields = ['song_title', 'song_artist', 'song_album'];

                    foreach ($search_fields as $field_name) {
                        if (stripos($row[$field_name], $search_phrase) !== false) {
                            return true;
                        }
                    }

                    return false;
                });
            }

            if ($request->hasParam('sort')) {
                $sort_by = [];
                foreach ($request->getParam('sort') as $sort_key => $sort_direction) {
                    $sort_dir = (strtolower($sort_direction) === 'desc') ? \SORT_DESC : \SORT_ASC;
                    $sort_by[] = $sort_key;
                    $sort_by[] = $sort_dir;
                }
            } else {
                $sort_by = ['song_artist', \SORT_ASC, 'song_title', \SORT_ASC];
            }

            $result = Utilities::array_order_by($result, $sort_by);

            $num_results = count($result);

            $page = $request->getParam('current', 1);
            $row_count = $request->getParam('rowCount', 15);

            $offset_start = ($page - 1) * $row_count;
            $return_result = array_slice($result, $offset_start, $row_count);

            return $response->withJson([
                'current' => $page,
                'rowCount' => $row_count,
                'total' => $num_results,
                'rows' => $return_result,
            ]);
        }

        return $response->withJson($result);
    }

    /**
     * @SWG\Post(path="/station/{station_id}/request/{request_id}",
     *   tags={"Stations: Song Requests"},
     *   description="Submit a song request.",
     *   @SWG\Parameter(ref="#/parameters/station_id_required"),
     *   @SWG\Parameter(
     *     name="request_id",
     *     description="The requestable song ID",
     *     type="integer",
     *     format="int64",
     *     in="path",
     *     required=true
     *   ),
     *   @SWG\Response(response=200, description="Success"),
     *   @SWG\Response(response=404, description="Station not found"),
     *   @SWG\Response(response=403, description="Station does not support requests")
     * )
     */
    public function submitAction(Request $request, Response $response, $station_id, $media_id): Response
    {
        /** @var Entity\Station $station */
        $station = $request->getAttribute('station');

        // Verify that the station supports requests.
        $ba = $this->adapters->getBackendAdapter($station);
        if (!$ba->supportsRequests() || !$station->getEnableRequests()) {
            return $response->withJson('This station does not accept requests currently.', 403);
        }

        try {
            /** @var Entity\Repository\StationRequestRepository $request_repo */
            $request_repo = $this->em->getRepository(Entity\StationRequest::class);
            $request_repo->submit($station, $media_id);

            return $response->withJson('Request submitted successfully.');
        } catch (\App\Exception $e) {
            return $response->withJson($e->getMessage(), 400);
        }
    }
}