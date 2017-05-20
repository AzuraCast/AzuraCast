<?php
namespace Controller\Api;

use App\Utilities;
use Entity;

class ListenersController extends BaseController
{
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

        $listeners_raw = $this->em->createQuery('SELECT l FROM Entity\Listener l
            WHERE l.station_id = :station_id
            AND l.timestamp_end = 0')
            ->setParameter('station_id', $station->id)
            ->getArrayResult();

        /** @var \App\Cache $cache */
        $cache = $this->di['cache'];

        $client = new \GuzzleHttp\Client(['base_uri' => 'http://ip-api.com/json/']);
        $http_requests = 0;

        $listeners = [];
        foreach($listeners_raw as $listener) {
            $api = [
                'ip' => $listener['listener_ip'],
                'user_agent' => $listener['listener_user_agent'],
                'timestamp' => $listener['timestamp_start'],
                'connected' => time()-$listener['timestamp_start'],
            ];

            $api['location'] = $cache->getOrSet('/ip/'.$api['ip'], function() use ($api, $client, $http_requests) {

                $http_requests++;
                if ($http_requests > 75) {
                    return null;
                }

                $response = $client->get($api['ip']);
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