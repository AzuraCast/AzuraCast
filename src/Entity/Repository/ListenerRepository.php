<?php

declare(strict_types=1);

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
    public function getUniqueListeners(
        Entity\Station $station,
        DateTimeInterface|int $start,
        DateTimeInterface|int $end
    ): int {
        if ($start instanceof DateTimeInterface) {
            $start = $start->getTimestamp();
        }
        if ($end instanceof DateTimeInterface) {
            $end = $end->getTimestamp();
        }

        return (int)$this->em->createQuery(
            <<<'DQL'
                SELECT COUNT(DISTINCT l.listener_hash)
                FROM App\Entity\Listener l
                WHERE l.station_id = :station_id
                AND l.timestamp_start <= :time_end
                AND l.timestamp_end >= :time_start
            DQL
        )->setParameter('station_id', $station->getId())
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
    public function update(Entity\Station $station, array $clients): void
    {
        $this->em->transactional(
            function () use ($station, $clients): void {
                $existingClientsRaw = $this->em->createQuery(
                    <<<'DQL'
                        SELECT l.id, l.listener_uid, l.listener_hash
                        FROM App\Entity\Listener l
                        WHERE l.station = :station
                        AND l.timestamp_end = 0
                    DQL
                )->setParameter('station', $station);

                $existingClientsIterator = $existingClientsRaw->toIterable([], $existingClientsRaw::HYDRATE_ARRAY);
                $existingClients = [];
                foreach ($existingClientsIterator as $client) {
                    $identifier = $client['listener_uid'] . '_' . $client['listener_hash'];
                    $existingClients[$identifier] = $client['id'];
                }

                $listenerTable = $this->em->getClassMetadata(Entity\Listener::class)->getTableName();

                $conn = $this->em->getConnection();

                foreach ($clients as $client) {
                    $listenerHash = Entity\Listener::calculateListenerHash($client);
                    $identifier = $client->uid . '_' . $listenerHash;

                    // Check for an existing record for this client.
                    if (isset($existingClients[$identifier])) {
                        unset($existingClients[$identifier]);
                    } else {
                        // Create a new record.
                        $record = [
                            'station_id' => $station->getId(),
                            'timestamp_start' => time(),
                            'timestamp_end' => 0,
                            'listener_uid' => (int)$client->uid,
                            'listener_user_agent' => mb_substr(
                                $client->userAgent ?? '',
                                0,
                                255,
                                'UTF-8'
                            ),
                            'listener_ip' => $client->ip,
                            'listener_hash' => Entity\Listener::calculateListenerHash($client),
                        ];

                        if (!empty($client->mount)) {
                            [$mountType, $mountId] = explode('_', $client->mount, 2);

                            if ('local' === $mountType) {
                                $record['mount_id'] = (int)$mountId;
                            } elseif ('remote' === $mountType) {
                                $record['remote_id'] = (int)$mountId;
                            }
                        }

                        $conn->insert($listenerTable, $record);
                    }
                }

                // Mark the end of all other clients on this station.
                if (!empty($existingClients)) {
                    $this->em->createQuery(
                        <<<'DQL'
                            UPDATE App\Entity\Listener l
                            SET l.timestamp_end = :time
                            WHERE l.id IN (:ids)
                        DQL
                    )->setParameter('time', time())
                        ->setParameter('ids', array_values($existingClients))
                        ->execute();
                }
            }
        );
    }

    public function clearAll(): void
    {
        $this->em->createQuery(
            <<<'DQL'
                DELETE FROM App\Entity\Listener l
            DQL
        )->execute();
    }

    public function cleanup(int $daysToKeep): void
    {
        $threshold = CarbonImmutable::now()
            ->subDays($daysToKeep)
            ->getTimestamp();

        $this->em->createQuery(
            <<<'DQL'
                DELETE FROM App\Entity\Listener sh
                WHERE sh.timestamp_start != 0
                AND sh.timestamp_start <= :threshold
            DQL
        )->setParameter('threshold', $threshold)
            ->execute();
    }
}
