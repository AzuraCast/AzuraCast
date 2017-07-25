<?php
namespace Controller\Api;

use App\Utilities;
use Entity;

class ListenersController extends BaseController
{
    /**
     * @SWG\Get(path="/station/{station_id}/listeners",
     *   tags={"Stations: Listeners"},
     *   description="Return detailed information about current listeners.",
     *   @SWG\Parameter(ref="#/parameters/station_id_required"),
     *   @SWG\Response(
     *     response=200,
     *     description="Success",
     *     @SWG\Schema(
     *       type="array",
     *       @SWG\Items(ref="#/definitions/Listener")
     *     )
     *   ),
     *   @SWG\Response(response=404, description="Station not found"),
     *   @SWG\Response(response=403, description="Access denied"),
     *   security={
     *     {"api_key": {"view station reports"}}
     *   },
     * )
     */
    public function indexAction()
    {
        try {
            $station = $this->getStation();
        } catch(\Exception $e) {
            return $this->returnError($e->getMessage());
        }

        try {
            $this->checkStationPermission($station, 'view station reports');
        } catch(\App\Exception\PermissionDenied $e) {
            return $this->returnError($e->getMessage(), 403);
        }

        if ($this->hasParam('start')) {

            $start = strtotime($this->getParam('start').' 00:00:00');
            $end = strtotime($this->getParam('end', $this->getParam('start')).' 23:59:59');

            $listeners_unsorted = $this->em->createQuery('SELECT l FROM Entity\Listener l
                WHERE l.station_id = :station_id
                AND l.timestamp_start < :end
                AND l.timestamp_end > :start')
                ->setParameter('station_id', $station->id)
                ->setParameter('start', $start)
                ->setParameter('end', $end)
                ->getArrayResult();

            $listeners_raw = [];
            foreach($listeners_unsorted as $listener) {

                $hash = $listener['listener_hash'];
                if (!isset($listeners_raw[$hash])) {
                    $listener['connected_time'] = 0;
                    $listeners_raw[$hash] = $listener;
                }

                $listeners_raw[$hash]['connected_time'] += ($listener['timestamp_end'] - $listener['timestamp_start']);
            }
        } else {
            $listeners_raw = $this->em->createQuery('SELECT l FROM Entity\Listener l
                WHERE l.station_id = :station_id
                AND l.timestamp_end = 0')
                ->setParameter('station_id', $station->id)
                ->getArrayResult();
        }

        /** @var \App\Cache $cache */
        $cache = $this->di['cache'];

        $client = new \GuzzleHttp\Client(['base_uri' => 'http://ip-api.com/json/']);
        $http_requests = 0;

        $listeners = [];
        foreach($listeners_raw as $listener) {
            $api = new Entity\Api\Listener;
            $api->ip = (string)$listener['listener_ip'];
            $api->user_agent = (string)$listener['listener_user_agent'];
            $api->connected_on = (int)$listener['timestamp_start'];
            $api->connected_time = $listener['connected_time'] ?? (time() - $listener['timestamp_start']);

            $api->location = $cache->getOrSet('/ip/' . $api->ip, function () use ($api, $client, $http_requests) {

                $http_requests++;
                if ($http_requests > 75) {
                    return null;
                }

                $response = $client->get($api->ip);
                if ($response->getStatusCode() == 200) {
                    $location_body = $response->getBody()->getContents();
                    $location = json_decode($location_body, true);
                    unset($location['query']);
                    return $location;
                }

                return null;

            }, 3600);

            $listeners[] = $api;
        }

        return $this->returnSuccess($listeners);
    }
}