<?php

namespace App\Entity\Repository;

use App\Doctrine\Repository;
use App\Entity;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use NowPlaying\Result\Client;

class ListenerRepository extends Repository
{
    /**
     * Get the number of unique listeners for a station during a specified time period.
     *
     * @param Entity\Station $station
     * @param DateTimeInterface|int $start
     * @param DateTimeInterface|int $end
     */
    public function getUniqueListeners(Entity\Station $station, $start, $end): int
    {
        if ($start instanceof DateTimeInterface) {
            $start = $start->getTimestamp();
        }
        if ($end instanceof DateTimeInterface) {
            $end = $end->getTimestamp();
        }

        return (int)$this->em->createQuery(/** @lang DQL */ 'SELECT
            COUNT(DISTINCT l.listener_hash)
            FROM App\Entity\Listener l
            WHERE l.station_id = :station_id
            AND l.timestamp_start <= :time_end
            AND l.timestamp_end >= :time_start')
            ->setParameter('station_id', $station->getId())
            ->setParameter('time_end', $end)
            ->setParameter('time_start', $start)
            ->getSingleScalarResult();
    }

    /**
     * Update listener data for a station.
     *
     * @param Entity\Station $station
     * @param Client[] $clients
     */
    public function update(Entity\Station $station, $clients): void
    {
        $existingClientsRaw = $this->em->createQuery(/** @lang DQL */ 'SELECT
            l.id, l.listener_uid, l.listener_hash
            FROM App\Entity\Listener l
            WHERE l.station = :station
            AND l.timestamp_end = 0')
            ->setParameter('station', $station)
            ->getArrayResult();

        $existingClients = [];
        foreach ($existingClientsRaw as $client) {
            $identifier = $client['listener_uid'] . '_' . $client['listener_hash'];
            $existingClients[$identifier] = $client['id'];
        }

        foreach ($clients as $client) {
            $listenerHash = Entity\Listener::calculateListenerHash($client);
            $identifier = $client->uid . '_' . $listenerHash;

            // Check for an existing record for this client.
            if (isset($existingClients[$identifier])) {
                unset($existingClients[$identifier]);
            } else {
                // Create a new record.
                $record = new Entity\Listener($station, $client);
                $this->em->persist($record);
            }
        }

        $this->em->flush();

        // Mark the end of all other clients on this station.
        if (!empty($existingClients)) {
            $this->em->createQuery(/** @lang DQL */ 'UPDATE App\Entity\Listener l
                SET l.timestamp_end = :time
                WHERE l.id IN (:ids)')
                ->setParameter('time', time())
                ->setParameter('ids', array_values($existingClients))
                ->execute();
        }
    }

    public function clearAll(): void
    {
        $this->em->createQuery(/** @lang DQL */ 'DELETE FROM App\Entity\Listener l')
            ->execute();
    }

    public function cleanup(int $daysToKeep): void
    {
        $threshold = CarbonImmutable::now()
            ->subDays($daysToKeep)
            ->getTimestamp();

        $this->em->createQuery(/** @lang DQL */ 'DELETE
                FROM App\Entity\Listener sh
                WHERE sh.timestamp_start != 0
                AND sh.timestamp_start <= :threshold')
            ->setParameter('threshold', $threshold)
            ->execute();
    }
}
