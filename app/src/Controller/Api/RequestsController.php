<?php
namespace Controller\Api;

use App\Url;
use App\Utilities;
use AzuraCast\Radio\Adapters;
use Doctrine\ORM\EntityManager;
use Entity;
use App\Http\Request;
use App\Http\Response;

class RequestsController
{
    /** @var EntityManager */
    protected $em;

    /** @var Url */
    protected $url;

    /** @var Adapters */
    protected $adapters;

    /**
     * RequestsController constructor.
     * @param EntityManager $em
     * @param Adapters $adapters
     * @param Url $url
     */
    public function __construct(EntityManager $em, Adapters $adapters, Url $url)
    {
        $this->em = $em;
        $this->adapters = $adapters;
        $this->url = $url;
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

        $ba = $this->adapters->getBackendAdapter($station);
        if (!$ba->supportsRequests()) {
            return $response->withJson('This station does not support requests.', 403);
        }

        $requestable_media = $this->em->createQuery('SELECT sm, s, sp 
            FROM Entity\StationMedia sm JOIN sm.song s LEFT JOIN sm.playlists sp
            WHERE sm.station_id = :station_id AND sp.id IS NOT NULL
            AND sp.is_enabled = 1 AND sp.type = :playlist_type')
            ->setParameter('station_id', $station->getId())
            ->setParameter('playlist_type', 'default')
            ->useResultCache(true, 60)
            ->execute();

        $result = [];

        foreach ($requestable_media as $media_row) {

            /** @var Entity\StationMedia $media_row */
            $song = $media_row->api($this->url);

            $request = new Entity\Api\StationRequest;
            $request->song = $song;
            $request->request_id = (int)$media_row->getId();
            $request->request_url = (string)$this->url->named('api:requests:submit', [
                'station' => $station_id,
                'media_id' => $media_row->getUniqueId()
            ]);
            $result[] = $request;
        }

        // Handle Bootgrid-style iteration through result
        if (!empty($_REQUEST['current'])) {
            $result = json_decode(json_encode($result), true);

            // Flatten the results array for bootgrid.
            foreach ($result as &$row) {
                foreach ($row['song'] as $song_key => $song_val) {
                    $row['song_' . $song_key] = $song_val;
                }
            }

            // Example from bootgrid docs:
            // current=1&rowCount=10&sort[sender]=asc&searchPhrase=&id=b0df282a-0d67-40e5-8558-c9e93b7befed

            // Apply sorting, limiting and searching.
            $search_phrase = trim($_REQUEST['searchPhrase']);

            if (!empty($search_phrase)) {
                $result = array_filter($result, function ($row) use ($search_phrase) {
                    $search_fields = ['song_title', 'song_artist'];

                    foreach ($search_fields as $field_name) {
                        if (stripos($row[$field_name], $search_phrase) !== false) {
                            return true;
                        }
                    }

                    return false;
                });
            }

            if (!empty($_REQUEST['sort'])) {
                $sort_by = [];
                foreach ($_REQUEST['sort'] as $sort_key => $sort_direction) {
                    $sort_dir = (strtolower($sort_direction) === 'desc') ? \SORT_DESC : \SORT_ASC;
                    $sort_by[] = $sort_key;
                    $sort_by[] = $sort_dir;
                }
            } else {
                $sort_by = ['song_artist', \SORT_ASC, 'song_title', \SORT_ASC];
            }

            $result = Utilities::array_order_by($result, $sort_by);

            $num_results = count($result);

            $page = @$_REQUEST['current'] ?: 1;
            $row_count = @$_REQUEST['rowCount'] ?: 15;

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

        $ba = $this->adapters->getBackendAdapter($station);
        if (!$ba->supportsRequests()) {
            return $response->withJson('This station does not support requests.', 403);
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