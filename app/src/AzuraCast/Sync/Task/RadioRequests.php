<?php
namespace AzuraCast\Sync\Task;

use AzuraCast\Radio\Adapters;
use Doctrine\ORM\EntityManager;
use Entity;

class RadioRequests extends TaskAbstract
{
    /** @var EntityManager */
    protected $em;

    /** @var Adapters */
    protected $adapters;

    /**
     * RadioRequests constructor.
     * @param EntityManager $em
     * @param Adapters $adapters
     */
    public function __construct(EntityManager $em, Adapters $adapters)
    {
        $this->em = $em;
        $this->adapters = $adapters;
    }

    /**
     * Manually process any requests for stations that use "Manual AutoDJ" mode.
     *
     * @param bool $force
     */
    public function run($force = false)
    {
        /** @var Entity\Repository\StationRepository $stations */
        $stations = $this->em->getRepository(Entity\Station::class)->findAll();

        /** @var Entity\Repository\StationRequestRepository $request_repo */
        $request_repo = $this->em->getRepository(Entity\StationRequest::class);

        foreach ($stations as $station) {
            /** @var Entity\Station $station */
            if (!$station->getEnableRequests() || !$station->useManualAutoDJ()) {
                continue;
            }

            $min_minutes = (int)$station->getRequestDelay();
            $threshold_minutes = $min_minutes + mt_rand(0, $min_minutes);

            $threshold = time() - ($threshold_minutes * 60);

            // Look up all requests that have at least waited as long as the threshold.
            $requests = $this->em->createQuery('SELECT sr, sm 
                FROM Entity\StationRequest sr JOIN sr.track sm
                WHERE sr.played_at = 0 AND sr.station_id = :station_id AND sr.timestamp <= :threshold
                ORDER BY sr.id ASC')
                ->setParameter('station_id', $station->getId())
                ->setParameter('threshold', $threshold)
                ->execute();

            foreach($requests as $request) {
                /** @var Entity\StationRequest $request */
                try {
                    $request_repo->checkRecentPlay($request->getTrack(), $station);
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
        // Send request to the station to play the request.
        $backend = $this->adapters->getBackendAdapter($station);

        if (!method_exists($backend, 'request')) {
            return false;
        }

        try {
            $backend->request($request->getTrack()->getFullPath());
        } catch(\Exception $e) {
            return false;
        }

        // Log the request as played.
        $request->setPlayedAt(time());

        $this->em->persist($request);
        $this->em->flush();

        return true;
    }
}