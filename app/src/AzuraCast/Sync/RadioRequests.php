<?php
namespace AzuraCast\Sync;

use Doctrine\ORM\EntityManager;
use Entity;
use Entity\Station;
use Entity\StationRequest;

class RadioRequests extends SyncAbstract
{
    /** @var EntityManager $em */
    protected $em;

    public function run()
    {
        $this->em = $this->di['em'];

        $stations = $this->em->getRepository(Station::class)->findAll();
        $request_repo = $this->em->getRepository(Entity\StationRequest::class);

        foreach ($stations as $station) {
            /** @var $station Station */
            if (!$station->enable_requests) {
                continue;
            }

            $min_minutes = (int)$station->request_delay;
            $threshold_minutes = $min_minutes + mt_rand(0, $min_minutes);

            \App\Debug::log($station->name . ': Random minutes threshold: ' . $threshold_minutes);

            $threshold = time() - ($threshold_minutes * 60);

            // Look up all requests that have at least waited as long as the threshold.
            $requests = $this->em->createQuery('SELECT sr, sm 
                FROM \Entity\StationRequest sr JOIN sr.track sm
                WHERE sr.played_at = 0 AND sr.station_id = :station_id AND sr.timestamp <= :threshold
                ORDER BY sr.id ASC')
                ->setParameter('station_id', $station->id)
                ->setParameter('threshold', $threshold)
                ->execute();

            foreach($requests as $request) {
                /** @var Entity\StationRequest $request */
                try {
                    $request_repo->checkRecentPlay($request->track, $station);
                    $this->_submitRequest($station, $request);
                    break;
                } catch(\Exception $e) {
                    continue;
                }
            }
        }
    }

    protected function _submitRequest(Entity\Station $station, Entity\StationRequest $request)
    {
        \App\Debug::log($station->name . ': Request to play ' . $request->track->artist . ' - ' . $request->track->title);

        // Send request to the station to play the request.
        $backend = $station->getBackendAdapter($this->di);

        if (!method_exists($backend, 'request')) {
            return false;
        }

        try {
            $backend->request($request->track->getFullPath());
        } catch(\Exception $e) {
            \App\Debug::log('Request error: '.$e->getMessage());
            return false;
        }

        // Log the request as played.
        $request->played_at = time();

        $this->em->persist($request);
        $this->em->flush();

        return true;
    }
}