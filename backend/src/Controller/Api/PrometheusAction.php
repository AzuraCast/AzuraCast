<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Container\EntityManagerAwareTrait;
use App\Container\EnvironmentAwareTrait;
use App\Controller\SingleActionInterface;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use App\Service\ServerStats;
use Brick\Math\BigDecimal;
use Brick\Math\BigInteger;
use Brick\Math\RoundingMode;
use OpenApi\Attributes as OA;
use Prometheus\CollectorRegistry;
use Prometheus\RenderTextFormat;
use Prometheus\Storage\InMemory;
use Psr\Http\Message\ResponseInterface;

#[
    OA\Get(
        path: '/prometheus',
        operationId: 'getPrometheus',
        summary: 'Returns the Prometheus measurements for this installation.',
        tags: [OpenApi::TAG_MISC],
        responses: [
            new OpenApi\Response\SuccessWithDownload(
                description: 'Success',
                content: new OA\MediaType(
                    mediaType: 'text/plain',
                    schema: new OA\Schema(
                        description: 'The Prometheus measurements for this installation.',
                        type: 'string',
                        format: 'binary'
                    )
                )
            ),
        ]
    )
]
final class PrometheusAction implements SingleActionInterface
{
    use EntityManagerAwareTrait;
    use EnvironmentAwareTrait;

    public const string APP_NAMESPACE = 'azuracast';

    public function __invoke(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $registry = new CollectorRegistry(new InMemory(), false);

        // System info
        $this->addCpuMeasurements($registry);
        $this->addRamMeasurements($registry);
        $this->addSpaceMeasurements($registry);

        // Station info
        $this->addStationMeasurements($registry);

        // Write response
        $response = $response->withHeader('Content-Type', RenderTextFormat::MIME_TYPE);
        $response->write((new RenderTextFormat())->render($registry->getMetricFamilySamples()));
        return $response;
    }

    private function addCpuMeasurements(CollectorRegistry $registry): void
    {
        [$cpu1MinValue, $cpu5MinValue, $cpu15MinValue] = sys_getloadavg() ?: [0, 0, 0];

        $registry->getOrRegisterGauge(
            self::APP_NAMESPACE,
            'system_cpu_load_1min',
            'The total CPU load over the last minute as a multiple of the available processors.',
        )->set($cpu1MinValue);

        $registry->getOrRegisterGauge(
            self::APP_NAMESPACE,
            'system_cpu_load_5min',
            'The total CPU load over the last 5 minutes as a multiple of the available processors.',
        )->set($cpu5MinValue);

        $registry->getOrRegisterGauge(
            self::APP_NAMESPACE,
            'system_cpu_load_15min',
            'The total CPU load over the last 15 minutes as a multiple of the available processors.',
        )->set($cpu15MinValue);
    }

    private function addRamMeasurements(CollectorRegistry $registry): void
    {
        $memoryStats = ServerStats::getMemoryUsage();

        $registry->getOrRegisterGauge(
            self::APP_NAMESPACE,
            'system_ram_total_mbytes',
            'The total available RAM (in megabytes)',
        )->set($this->toMegabytes($memoryStats->memTotal));

        $registry->getOrRegisterGauge(
            self::APP_NAMESPACE,
            'system_ram_free_mbytes',
            'The free, unused RAM (in megabytes)',
        )->set($this->toMegabytes($memoryStats->memFree));

        $registry->getOrRegisterGauge(
            self::APP_NAMESPACE,
            'system_ram_used_mbytes',
            'The used RAM (in megabytes)',
        )->set($this->toMegabytes($memoryStats->getUsedMemory()));
    }

    private function addSpaceMeasurements(CollectorRegistry $registry): void
    {
        $spaceTotalFloat = disk_total_space($this->environment->getStationDirectory());
        $spaceTotal = (is_float($spaceTotalFloat))
            ? BigInteger::of($spaceTotalFloat)
            : BigInteger::zero();

        $registry->getOrRegisterGauge(
            self::APP_NAMESPACE,
            'system_disk_total_mbytes',
            'The total available and used disk space in the station directory (in megabytes)',
        )->set($this->toMegabytes($spaceTotal));

        $spaceFreeFloat = disk_free_space($this->environment->getStationDirectory());
        $spaceFree = (is_float($spaceFreeFloat))
            ? BigInteger::of($spaceFreeFloat)
            : BigInteger::zero();

        $registry->getOrRegisterGauge(
            self::APP_NAMESPACE,
            'system_disk_free_mbytes',
            'The total free disk space in the station directory (in megabytes)',
        )->set($this->toMegabytes($spaceFree));

        $spaceUsed = $spaceTotal->minus($spaceFree);

        $registry->getOrRegisterGauge(
            self::APP_NAMESPACE,
            'system_disk_used_mbytes',
            'The total used disk space in the station directory (in megabytes)',
        )->set($this->toMegabytes($spaceUsed));
    }

    private function addStationMeasurements(CollectorRegistry $registry): void
    {
        $activeStations = $this->em->createQuery(
            <<<DQL
                SELECT s, m, r, h FROM App\Entity\Station s
                LEFT JOIN s.mounts m
                LEFT JOIN s.remotes r
                LEFT JOIN s.hls_streams h
                WHERE s.is_enabled = 1
            DQL
        )->getArrayResult();

        $registry->getOrRegisterGauge(
            self::APP_NAMESPACE,
            'active_stations',
            'The total number of active stations on this installation.',
        )->set((float)count($activeStations));

        $stationTotalGauge = $registry->getOrRegisterGauge(
            self::APP_NAMESPACE,
            'station_listeners_total',
            'The total current listener count (with possible duplicates) for each given station.',
            ['station']
        );

        $stationUniqueGauge = $registry->getOrRegisterGauge(
            self::APP_NAMESPACE,
            'station_listeners_unique',
            'The unique current listener count for each station.',
            ['station']
        );

        $streamTotalGauge = $registry->getOrRegisterGauge(
            self::APP_NAMESPACE,
            'stream_listeners_total',
            'The total current listener count (with possible duplicates) for a specific stream on the given station.',
            ['station', 'stream_type', 'stream_id']
        );

        $streamUniqueGauge = $registry->getOrRegisterGauge(
            self::APP_NAMESPACE,
            'stream_listeners_unique',
            'The unique current listener count for a specific stream on the given station.',
            ['station', 'stream_type', 'stream_id']
        );

        foreach ($activeStations as $station) {
            $stationShortCode = $station['short_name'];
            $uniqueListeners = 0;
            $totalListeners = 0;

            foreach ($station['mounts'] as $mount) {
                $uniqueListeners += $mount['listeners_unique'];
                $totalListeners += $mount['listeners_total'];

                $streamUniqueGauge->set(
                    (float)$mount['listeners_unique'],
                    [$stationShortCode, 'mount', $mount['id']]
                );
                $streamTotalGauge->set(
                    (float)$mount['listeners_total'],
                    [$stationShortCode, 'mount', $mount['id']]
                );
            }

            foreach ($station['hls_streams'] as $stream) {
                $uniqueListeners += $stream['listeners'];
                $totalListeners += $stream['listeners'];

                $streamUniqueGauge->set(
                    (float)$stream['listeners'],
                    [$stationShortCode, 'hls', $stream['id']]
                );
                $streamTotalGauge->set(
                    (float)$stream['listeners'],
                    [$stationShortCode, 'hls', $stream['id']]
                );
            }

            foreach ($station['remotes'] as $remote) {
                $uniqueListeners += $remote['listeners_unique'];
                $totalListeners += $remote['listeners_total'];

                $streamUniqueGauge->set(
                    (float)$remote['listeners_unique'],
                    [$stationShortCode, 'remote', $remote['id']]
                );
                $streamTotalGauge->set(
                    (float)$remote['listeners_total'],
                    [$stationShortCode, 'remote', $remote['id']]
                );
            }

            $stationUniqueGauge->set(
                (float)$uniqueListeners,
                [$stationShortCode]
            );
            $stationTotalGauge->set(
                (float)$totalListeners,
                [$stationShortCode]
            );
        }
    }

    private function toMegabytes(BigInteger|BigDecimal $bytes): float
    {
        if ($bytes instanceof BigInteger) {
            $bytes = $bytes->toBigDecimal();
        }

        $mbytes = BigInteger::of(1024)->power(2);
        return $bytes->dividedBy($mbytes, 2, RoundingMode::HALF_UP)->toFloat();
    }
}
