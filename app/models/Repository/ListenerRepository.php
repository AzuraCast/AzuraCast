<?php
namespace Entity\Repository;

use Entity;

class ListenerRepository extends \App\Doctrine\Repository
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
        return $this->_em->createQuery('SELECT COUNT(l.id)
            FROM '.$this->_entityName.' l
            WHERE l.station_id = :station_id
            AND l.timestamp_start <= :end
            AND l.timestamp_end >= :start')
            ->setParameter('station_id', $station->id)
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
                $existing_id = $this->_em->createQuery('SELECT l.id FROM '.$this->_entityName.' l
                    WHERE l.station_id = :station_id
                    AND l.listener_uid = :uid
                    AND l.listener_ip = :ip
                    AND l.timestamp_end = 0')
                        ->setParameter('station_id', $station->id)
                        ->setParameter('uid', $client['uid'])
                        ->setParameter('ip', $client['ip'])
                        ->getSingleScalarResult();

                $listener_ids[] = $existing_id;
            } catch(\Doctrine\ORM\NoResultException $e) {
                // Create a new record.
                $record = new Entity\Listener;
                $record->station = $station;
                $record->listener_uid = $client['uid'];
                $record->listener_ip = $client['ip'];
                $record->listener_user_agent = $client['user_agent'];

                $this->_em->persist($record);
                $this->_em->flush();

                $listener_ids[] = $record->id;
            }
        }

        // Mark the end of all other clients on this station.
        $this->_em->createQuery('UPDATE '.$this->_entityName.' l
            SET l.timestamp_end = :time
            WHERE l.station_id = :station_id
            AND l.timestamp_end = 0
            AND l.id NOT IN (:ids)')
            ->setParameter('time', time())
            ->setParameter('station_id', $station->id)
            ->setParameter('ids', $listener_ids)
            ->execute();
    }
}