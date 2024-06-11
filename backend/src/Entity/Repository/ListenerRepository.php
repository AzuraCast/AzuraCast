<?php

declare(strict_types=1);

namespace App\Entity\Repository;

use App\Container\LoggerAwareTrait;
use App\Doctrine\ReloadableEntityManagerInterface;
use App\Doctrine\Repository;
use App\Entity\Listener;
use App\Entity\Station;
use App\Entity\Traits\TruncateStrings;
use App\Service\DeviceDetector;
use App\Service\IpGeolocation;
use App\Utilities\File;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use DI\Attribute\Inject;
use Doctrine\DBAL\Connection;
use League\Csv\Writer;
use NowPlaying\Result\Client;
use Symfony\Component\Filesystem\Filesystem;
use Throwable;

/**
 * @extends Repository<Listener>
 */
final class ListenerRepository extends Repository
{
    use LoggerAwareTrait;
    use TruncateStrings;

    protected string $entityClass = Listener::class;

    private string $tableName;

    private Connection $conn;

    public function __construct(
        private readonly DeviceDetector $deviceDetector,
        private readonly IpGeolocation $ipGeolocation
    ) {
    }

    #[Inject]
    public function setEntityManager(ReloadableEntityManagerInterface $em): void
    {
        parent::setEntityManager($em);

        $this->tableName = $this->em->getClassMetadata(Listener::class)->getTableName();
        $this->conn = $this->em->getConnection();
    }

    /**
     * Get the number of unique listeners for a station during a specified time period.
     *
     * @param Station $station
     * @param DateTimeInterface|int $start
     * @param DateTimeInterface|int $end
     */
    public function getUniqueListeners(
        Station $station,
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

    public function iterateLiveListenersArray(Station $station): iterable
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
     * @param Station $station
     * @param Client[] $clients
     */
    public function update(Station $station, array $clients): void
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

                $this->batchAddClients(
                    $station,
                    $clients,
                    $existingClients
                );

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

    private function batchAddClients(
        Station $station,
        array &$clients,
        array &$existingClients
    ): void {
        $tempCsvPath = File::generateTempPath('mariadb_listeners.csv');
        (new Filesystem())->chmod($tempCsvPath, 0o777);

        $csv = Writer::createFromPath($tempCsvPath);
        $csv->setEscape('');
        $csv->addFormatter(function ($row) {
            return array_map(function ($col) {
                if (null === $col) {
                    return '\N';
                }

                return is_string($col)
                    ? str_replace('"', '""', $col)
                    : $col;
            }, $row);
        });

        $csvColumns = null;

        foreach ($clients as $client) {
            $identifier = Listener::calculateListenerHash($client);

            // Check for an existing record for this client.
            if (isset($existingClients[$identifier])) {
                unset($existingClients[$identifier]);
            } else {
                // Create a new record.
                $record = $this->batchAddRow($station, $client);

                if (null === $csvColumns) {
                    $csvColumns = array_keys($record);
                }

                $csv->insertOne($record);
            }
        }

        if (null === $csvColumns) {
            @unlink($tempCsvPath);
            return;
        }

        // Use LOAD DATA INFILE for listener dumps
        $csvLoadQuery = sprintf(
            <<<'SQL'
                LOAD DATA LOCAL INFILE %s IGNORE
                INTO TABLE %s 
                FIELDS TERMINATED BY ','
                OPTIONALLY ENCLOSED BY '"'
                LINES TERMINATED BY '\n'
                (%s)
            SQL,
            $this->conn->quote($tempCsvPath),
            $this->conn->quoteIdentifier($this->tableName),
            implode(
                ',',
                array_map(
                    fn($col) => $this->conn->quoteIdentifier($col),
                    $csvColumns
                )
            )
        );

        try {
            $this->conn->executeQuery($csvLoadQuery);
        } finally {
            @unlink($tempCsvPath);
        }

        $this->deviceDetector->saveCache();
        $this->ipGeolocation->saveCache();
    }

    private function batchAddRow(Station $station, Client $client): array
    {
        $record = [
            'station_id' => $station->getId(),
            'timestamp_start' => time(),
            'timestamp_end' => 0,
            'listener_uid' => (int)$client->uid,
            'listener_user_agent' => $this->truncateString($client->userAgent ?? ''),
            'listener_ip' => $client->ip,
            'listener_hash' => Listener::calculateListenerHash($client),
            'mount_id' => null,
            'remote_id' => null,
            'hls_stream_id' => null,
            'device_client' => null,
            'device_is_browser' => null,
            'device_is_mobile' => null,
            'device_is_bot' => null,
            'device_browser_family' => null,
            'device_os_family' => null,
            'location_description' => null,
            'location_region' => null,
            'location_city' => null,
            'location_country' => null,
            'location_lat' => null,
            'location_lon' => null,
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

        $this->batchAddDeviceDetails($record);
        $this->batchAddLocationDetails($record);

        return $record;
    }

    private function batchAddDeviceDetails(array &$record): void
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
            $this->logger->error('Device Detector error: ' . $e->getMessage(), [
                'user_agent' => $userAgent,
                'exception' => $e,
            ]);
        }
    }

    private function batchAddLocationDetails(array &$record): void
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
            $this->logger->error('IP Geolocation error: ' . $e->getMessage(), [
                'ip' => $ip,
                'exception' => $e,
            ]);

            $record['location_description'] = 'Unknown';
        }
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
