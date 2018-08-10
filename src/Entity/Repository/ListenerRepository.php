<?php
namespace App\Entity\Repository;

use App\Entity;
use Doctrine\ORM\NoResultException;

class ListenerRepository extends BaseRepository
{
    /**
     * Get the number of unique listeners for a station during a specified time period.
     *
     * @param Entity\Station $station
     * @param $timestamp_start
     * @param $timestamp_end
     * @return mixed
     */
    public function getUniqueListeners(Entity\Station $station, $timestamp_start, $timestamp_end)
    {
        return $this->_em->createQuery('SELECT COUNT(DISTINCT l.listener_hash)
            FROM '.$this->_entityName.' l
            WHERE l.station_id = :station_id
            AND l.timestamp_start <= :end
            AND l.timestamp_end >= :start')
            ->setParameter('station_id', $station->getId())
            ->setParameter('end', $timestamp_end)
            ->setParameter('start', $timestamp_start)
            ->getSingleScalarResult();
    }

    /**
     * Update listener data for a station.
     *
     * @param Entity\Station $station
     * @param $clients
     */
    public function update(Entity\Station $station, $clients)
    {
        $clients = (array)$clients;

        $listener_ids = [0];

        foreach($clients as $client) {
            // Check for an existing record for this client.
            try {
                $listener_hash = Entity\Listener::calculateListenerHash($client);

                /** @throws NoResultException */
                $existing_id = $this->_em->createQuery('SELECT l.id FROM '.$this->_entityName.' l
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
        $this->_em->createQuery('UPDATE '.$this->_entityName.' l
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
