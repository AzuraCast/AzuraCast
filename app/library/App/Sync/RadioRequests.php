<?php
namespace App\Sync;

use Doctrine\ORM\EntityManager;
use Entity\Station;

class RadioRequests extends SyncAbstract
{
    public function run()
    {
        /** @var EntityManager $em */
        $em = $this->di['em'];

        $stations = $em->getRepository(Station::class)->findAll();

        foreach($stations as $station)
        {
            if (!$station->enable_requests)
                continue;

            $min_minutes = (int)$station->request_delay;
            $threshold_minutes = $min_minutes + mt_rand(0, $min_minutes);

            \App\Debug::log($station->name . ': Random minutes threshold: ' . $threshold_minutes);

            $threshold = time() - ($threshold_minutes * 60);

            // Look up all requests that have at least waited as long as the threshold.
            $requests = $em->createQuery('SELECT sr, sm FROM \Entity\StationRequest sr JOIN sr.track sm
                WHERE sr.played_at = 0 AND sr.station_id = :station_id AND sr.timestamp <= :threshold
                ORDER BY sr.id ASC')
                ->setParameter('station_id', $station->id)
                ->setParameter('threshold', $threshold)
                ->execute();

            foreach ($requests as $request)
            {
                \App\Debug::log($station->name . ': Request to play ' . $request->track->artist . ' - ' . $request->track->title);

                // Log the request as played.
                $request->played_at = time();

                $em->persist($request);
                $em->flush();

                // Send request to the station to play the request.
                $backend = $station->getBackendAdapter();
                $backend->request($request->track->getFullPath());
            }
        }
    }
}