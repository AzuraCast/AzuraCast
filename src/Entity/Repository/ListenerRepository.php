<?php

declare(strict_types=1);

namespace App\Entity\Repository;

use App\Doctrine\ReloadableEntityManagerInterface;
use App\Doctrine\Repository;
use App\Entity;
use App\Service\DeviceDetector;
use App\Service\IpGeolocation;
use App\Utilities\Logger;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Doctrine\DBAL\Connection;
use NowPlaying\Result\Client;
use Throwable;

/**
 * @extends Repository<Entity\Listener>
 */
final class ListenerRepository extends Repository
{
    use Entity\Traits\TruncateStrings;

    private string $tableName;

    private Connection $conn;

    public function __construct(
        ReloadableEntityManagerInterface $em,
        private readonly DeviceDetector $deviceDetector,
        private readonly IpGeolocation $ipGeolocation
    ) {
        parent::__construct($em);

        $this->tableName = $this->em->getClassMetadata(Entity\Listener::class)->getTableName();
        $this->conn = $this->em->getConnection();
    }

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

    public function iterateLiveListenersArray(Entity\Station $station): iterable
    {
        $query = $this->em->createQuery(
            <<<'DQL'
                    SELECT l
                    FROM App\Entity\Listener l
                    WHERE l.station = :station
                    AND l.timestamp_end = 0
                    ORDER BY l.timestamp_start ASC
                DQL
        )->setParameter('station', $station);

        return $query->toIterable([], $query::HYDRATE_ARRAY);
    }

    /**
     * Update listener data for a station.
     *
     * @param Entity\Station $station
     * @param Client[] $clients
     */
    public function update(Entity\Station $station, array $clients): void
    {
        $this->em->wrapInTransaction(
            function () use ($station, $clients): void {
                $existingClientsRaw = $this->em->createQuery(
                    <<<'DQL'
                        SELECT l.id, l.listener_hash
                        FROM App\Entity\Listener l
                        WHERE l.station = :station
                        AND l.timestamp_end = 0
                    DQL
                )->setParameter('station', $station);

                $existingClientsIterator = $existingClientsRaw->toIterable([], $existingClientsRaw::HYDRATE_ARRAY);
                $existingClients = [];
                foreach ($existingClientsIterator as $client) {
                    $existingClients[$client['listener_hash']] = $client['id'];
                }

                foreach ($clients as $client) {
                    $identifier = Entity\Listener::calculateListenerHash($client);

                    // Check for an existing record for this client.
                    if (isset($existingClients[$identifier])) {
                        unset($existingClients[$identifier]);
                    } else {
                        // Create a new record.
                        $this->batchAddRow($station, $client);
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

    public function batchAddRow(Entity\Station $station, Client $client): array
    {
        $record = [
            'station_id' => $station->getId(),
            'timestamp_start' => time(),
            'timestamp_end' => 0,
            'listener_uid' => (int)$client->uid,
            'listener_user_agent' => $this->truncateString($client->userAgent ?? ''),
            'listener_ip' => $client->ip,
            'listener_hash' => Entity\Listener::calculateListenerHash($client),
        ];

        if (!empty($client->mount)) {
            [$mountType, $mountId] = explode('_', $client->mount, 2);

            if ('local' === $mountType) {
                $record['mount_id'] = (int)$mountId;
            } elseif ('remote' === $mountType) {
                $record['remote_id'] = (int)$mountId;
            } elseif ('hls' === $mountType) {
                $record['hls_stream_id'] = (int)$mountId;
            }
        }

        $record = $this->batchAddDeviceDetails($record);
        $record = $this->batchAddLocationDetails($record);

        $this->conn->insert($this->tableName, $record);

        return $record;
    }

    private function batchAddDeviceDetails(array $record): array
    {
        $userAgent = $record['listener_user_agent'];

        try {
            $browserResult = $this->deviceDetector->parse($userAgent);

            $record['device_client'] = $this->truncateNullableString($browserResult->client);
            $record['device_is_browser'] = $browserResult->isBrowser ? 1 : 0;
            $record['device_is_mobile'] = $browserResult->isMobile ? 1 : 0;
            $record['device_is_bot'] = $browserResult->isBot ? 1 : 0;
            $record['device_browser_family'] = $this->truncateNullableString($browserResult->browserFamily, 150);
            $record['device_os_family'] = $this->truncateNullableString($browserResult->osFamily, 150);
        } catch (Throwable $e) {
            Logger::getInstance()->error('Device Detector error: ' . $e->getMessage(), [
                'user_agent' => $userAgent,
                'exception' => $e,
            ]);
        }

        return $record;
    }

    private function batchAddLocationDetails(array $record): array
    {
        $ip = $record['listener_ip'];

        try {
            $ipInfo = $this->ipGeolocation->getLocationInfo($ip);

            $record['location_description'] = $this->truncateString($ipInfo->description);
            $record['location_region'] = $this->truncateNullableString($ipInfo->region, 150);
            $record['location_city'] = $this->truncateNullableString($ipInfo->city, 150);
            $record['location_country'] = $this->truncateNullableString($ipInfo->country, 2);
            $record['location_lat'] = $ipInfo->lat;
            $record['location_lon'] = $ipInfo->lon;
        } catch (Throwable $e) {
            Logger::getInstance()->error('IP Geolocation error: ' . $e->getMessage(), [
                'ip' => $ip,
                'exception' => $e,
            ]);

            $record['location_description'] = 'Unknown';
        }

        return $record;
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
