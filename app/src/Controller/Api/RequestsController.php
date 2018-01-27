<?php
namespace Controller\Api;

use App\Utilities;
use Entity;

class RequestsController extends BaseController
{
    protected function preDispatch()
    {
        parent::preDispatch();

        $rate_limit_timeout = 5;

        try {
            /** @var \AzuraCast\RateLimit $rate_limit */
            $rate_limit = $this->di[\AzuraCast\RateLimit::class];
            $rate_limit->checkRateLimit('api', $rate_limit_timeout, 2);
        } catch(\AzuraCast\Exception\RateLimitExceeded $e) {
            return $this->returnError('You have temporarily exceeded the rate limit for this application. Please wait '.$rate_limit_timeout.' seconds before attempting new requests.', 429);
        }
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
    public function listAction()
    {
        try {
            $station = $this->getStation();
        } catch(\Exception $e) {
            return $this->returnError($e->getMessage());
        }

        $ba = $station->getBackendAdapter($this->di);
        if (!$ba->supportsRequests()) {
            return $this->returnError('This station does not support requests.', 403);
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
            $request->request_url = (string)$this->url->routeFromHere([
                'action' => 'submit',
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
                    $sort_dir = (strtolower($sort_direction) == 'desc') ? \SORT_DESC : \SORT_ASC;
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

            return $this->renderJson([
                'current' => $page,
                'rowCount' => $row_count,
                'total' => $num_results,
                'rows' => $return_result,
            ]);
        }

        return $this->returnSuccess($result);
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
    public function submitAction()
    {
        try {
            $station = $this->getStation();
        } catch(\Exception $e) {
            return $this->returnError($e->getMessage());
        }

        $ba = $station->getBackendAdapter($this->di);
        if (!$ba->supportsRequests()) {
            return $this->returnError('This station does not support requests.', 403);
        }

        $song = $this->getParam('media_id');

        try {
            $this->em->getRepository(Entity\StationRequest::class)->submit($station, $song, $this->authenticate());

            return $this->returnSuccess('Request submitted successfully.');
        } catch (\App\Exception $e) {
            return $this->returnError($e->getMessage());
        }
    }
}