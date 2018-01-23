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
                ->setParameter('station_id', $station->getId())
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
                ->setParameter('station_id', $station->getId())
                ->getArrayResult();
        }

        $ips = [];
        foreach($listeners_raw as $listener) {
            $ips[$listener['listener_ip']] = $listener['listener_ip'];
        }

        $ip_info = $this->_getIpInfo($ips);

        $detect = new \Mobile_Detect;

        $listeners = [];
        foreach($listeners_raw as $listener) {
            $detect->setUserAgent($listener['listener_user_agent']);

            $api = new Entity\Api\Listener;
            $api->ip = (string)$listener['listener_ip'];
            $api->user_agent = (string)$listener['listener_user_agent'];
            $api->is_mobile = $detect->isMobile();
            $api->connected_on = (int)$listener['timestamp_start'];
            $api->connected_time = $listener['connected_time'] ?? (time() - $listener['timestamp_start']);
            $api->location = $ip_info[$listener['listener_ip']];

            $listeners[] = $api;
        }

        return $this->returnSuccess($listeners);
    }

    protected function _getIpInfo($raw_ips)
    {
        /** @var \App\Cache $cache */
        $cache = $this->di[\App\Cache::class];

        $return = [];
        foreach($raw_ips as $ip) {
            $ip_info = $cache->get('/ip/'.$ip, null);
            if ($ip_info !== null) {
                $return[$ip] = $ip_info;
                unset($raw_ips[$ip]);
            }
        }

        if (empty($raw_ips)) {
            return $return;
        }

        // Set up IP API batch query process.
        $client = new \GuzzleHttp\Client([
            'base_uri' => 'http://ip-api.com/batch',
            'timeout' => 10,
        ]);

        $ips_per_request = 90;

        for($i = 0; $i <= count($raw_ips); $i += $ips_per_request) {

            $ips = array_slice($raw_ips, $i, $ips_per_request);

            $batch_json = [];
            foreach($ips as $ip) {
                $batch_json[] = ['query' => $ip];
            }

            $response = $client->post('', [
                'json' => $batch_json,
            ]);

            if ($response->getStatusCode() == 200) {
                $response_body = $response->getBody()->getContents();
                $response = json_decode($response_body, true);

                foreach($response as $location_row) {
                    $ip = $location_row['query'];
                    unset($location_row['query']);

                    $cache->set($location_row, '/ip/'.$ip, 3600);
                    $return[$ip] = $location_row;
                }
            }
        }

        return $return;
    }
}