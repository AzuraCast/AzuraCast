<?php
namespace App\Entity\Repository;

use App\Entity;
use Azura\Doctrine\Repository;
use Doctrine\ORM\NoResultException;

class ListenerRepository extends Repository
{
    /**
     * Get the number of unique listeners for a station during a specified time period.
     *
     * @param Entity\Station $station
     * @param int $timestamp_start
     * @param int $timestamp_end
     * @return mixed
     */
    public function getUniqueListeners(Entity\Station $station, $timestamp_start, $timestamp_end)
    {
        return $this->_em->createQuery(/** @lang DQL */'SELECT 
            COUNT(DISTINCT l.listener_hash)
            FROM App\Entity\Listener l
            WHERE l.station_id = :station_id
            AND l.timestamp_start <= :time_end
            AND l.timestamp_end >= :time_start')
            ->setParameter('station_id', $station->getId())
            ->setParameter('time_end', $timestamp_end)
            ->setParameter('time_start', $timestamp_start)
            ->getSingleScalarResult();
    }

    /**
     * Update listener data for a station.
     *
     * @param Entity\Station $station
     * @param array $clients
     */
    public function update(Entity\Station $station, $clients): void
    {
        $clients = (array)$clients;

        $listener_ids = [0];

        foreach($clients as $client) {
            // Check for an existing record for this client.
            try {
                $listener_hash = Entity\Listener::calculateListenerHash($client);

                /** @throws NoResultException */
                $existing_id = $this->_em->createQuery(/** @lang DQL */'SELECT 
                    l.id 
                    FROM App\Entity\Listener l
                    WHERE l.station_id = :station_id
                    AND l.listener_uid = :uid
                    AND l.listener_hash = :hash
                    AND l.timestamp_end = 0')
                        ->setParameter('station_id', $station->getId())
                        ->setParameter('uid', $client['uid'])
                        ->setParameter('hash', $listener_hash)
                        ->getSingleScalarResult();

                if ($existing_id !== null) {
                    $listener_ids[] = $existing_id;
                }
            } catch(\Doctrine\ORM\NoResultException $e) {
                $existing_id = null;
            }

            if ($existing_id === null) {
                // Create a new record.
                $record = new Entity\Listener($station, $client);
                $this->_em->persist($record);
                $this->_em->flush();

                $listener_ids[] = $record->getId();
            }
        }

        // Mark the end of all other clients on this station.
        $this->_em->createQuery(/** @lang DQL */'UPDATE App\Entity\Listener l
            SET l.timestamp_end = :time
            WHERE l.station_id = :station_id
            AND l.timestamp_end = 0
            AND l.id NOT IN (:ids)')
            ->setParameter('time', time())
            ->setParameter('station_id', $station->getId())
            ->setParameter('ids', $listener_ids)
            ->execute();
    }
}
