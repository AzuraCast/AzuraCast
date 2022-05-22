<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin;

use App\Environment;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use App\Radio\Quota;
use App\Service\CpuStats;
use App\Service\MemoryStats;
use App\Service\NetworkStats;
use Brick\Math\BigInteger;
use Brick\Math\RoundingMode;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[
    OA\Get(
        path: '/admin/cpu/stats',
        operationId: 'getCpuStats',
        description: 'Return a list of all CPU usage stats.',
        security: OpenApi::API_KEY_SECURITY,
        tags: ['Administration: CPU stats'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success' // TODO: Response Body
            ),
            new OA\Response(ref: OpenApi::REF_RESPONSE_ACCESS_DENIED, response: 403),
            new OA\Response(ref: OpenApi::REF_RESPONSE_NOT_FOUND, response: 404),
            new OA\Response(ref: OpenApi::REF_RESPONSE_GENERIC_ERROR, response: 500),
        ]
    )
]
final class ServerStatsAction
{
    public function __construct(
        private readonly Environment $environment,
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response
    ): ResponseInterface {
        $firstCpuMeasurement = CpuStats::getCurrentLoad();
        $firstNetworkMeasurement = NetworkStats::getNetworkUsage();

        $measurementTime = 1;
        sleep($measurementTime);

        $secondCpuMeasurement = CpuStats::getCurrentLoad();
        $secondNetworkMeasurement = NetworkStats::getNetworkUsage();

        $cpuTotal = [];
        $statsPerCore = [];

        foreach ($secondCpuMeasurement as $index => $currentCoreData) {
            $previousCpuData = $firstCpuMeasurement[$index];
            $deltaCpuData = CpuStats::calculateDelta($currentCoreData, $previousCpuData);

            $cpuStats = [
                'name' => $deltaCpuData->name,
                'usage' => CpuStats::getUsage($deltaCpuData),
                'idle' => CpuStats::getIdle($deltaCpuData),
                'io_wait' => CpuStats::getIoWait($deltaCpuData),
                'steal' => CpuStats::getSteal($deltaCpuData),
            ];

            if ($deltaCpuData->name === 'total') {
                $cpuTotal = $cpuStats;
            } else {
                $statsPerCore[] = $cpuStats;
            }
        }

        $networkInterfaces = [];

        foreach ($secondNetworkMeasurement as $index => $currentNetworkMeasurement) {
            $previousNetworkMeasurement = $firstNetworkMeasurement[$index];
            $deltaNetworkData = NetworkStats::calculateDelta(
                $currentNetworkMeasurement,
                $previousNetworkMeasurement
            );

            $bytesPerTimeReceived = $deltaNetworkData->received->bytes
                ->dividedBy($measurementTime, RoundingMode::HALF_UP)
                ->toBigInteger();

            $bytesPerTimeTransmitted = $deltaNetworkData->transmitted->bytes
                ->dividedBy($measurementTime, RoundingMode::HALF_UP)
                ->toBigInteger();

            $networkInterfaceStats = [
                'interface_name' => $deltaNetworkData->interfaceName,
                'received' => [
                    'speed' => [
                        'bytes' => $bytesPerTimeReceived,
                        'readable' => Quota::getReadableSize($bytesPerTimeReceived),
                    ],
                    'packets' => $deltaNetworkData->received->packets,
                    'errs' => $deltaNetworkData->received->errs,
                    'drop' => $deltaNetworkData->received->drop,
                    'fifo' => $deltaNetworkData->received->fifo,
                    'frame' => $deltaNetworkData->received->frame,
                    'compressed' => $deltaNetworkData->received->compressed,
                    'multicast' => $deltaNetworkData->received->multicast,
                ],
                'transmitted' => [
                    'speed' => [
                        'bytes' => $bytesPerTimeTransmitted,
                        'readable' => Quota::getReadableSize($bytesPerTimeTransmitted),
                    ],
                    'packets' => $deltaNetworkData->transmitted->packets,
                    'errs' => $deltaNetworkData->transmitted->errs,
                    'drop' => $deltaNetworkData->transmitted->drop,
                    'fifo' => $deltaNetworkData->transmitted->fifo,
                    'frame' => $deltaNetworkData->transmitted->colls,
                    'carrier' => $deltaNetworkData->transmitted->carrier,
                    'compressed' => $deltaNetworkData->transmitted->compressed,
                ],
            ];

            $networkInterfaces[] = $networkInterfaceStats;
        }

        $memoryStats = MemoryStats::getMemoryUsage();

        $spaceTotalFloat = disk_total_space($this->environment->getStationDirectory());
        $spaceTotal = (is_float($spaceTotalFloat))
            ? BigInteger::of($spaceTotalFloat)
            : BigInteger::zero();

        $spaceFreeFloat = disk_free_space($this->environment->getStationDirectory());
        $spaceFree = (is_float($spaceFreeFloat))
            ? BigInteger::of($spaceFreeFloat)
            : BigInteger::zero();

        $spaceUsed = $spaceTotal->minus($spaceFree);

        $stats = [
            'cpu' => [
                'total' => $cpuTotal,
                'cores' => $statsPerCore,
                'load' => sys_getloadavg(),
            ],
            'memory' => [
                'bytes' => [
                    'total' => $memoryStats->memTotal,
                    'free' => $memoryStats->memFree,
                    'cached' => $memoryStats->cached,
                    'used' => $memoryStats->getUsedMemory(),
                ],
                'readable' => [
                    'total' => Quota::getReadableSize($memoryStats->memTotal),
                    'free' => Quota::getReadableSize($memoryStats->memFree),
                    'cached' => Quota::getReadableSize($memoryStats->cached),
                    'used' => Quota::getReadableSize($memoryStats->getUsedMemory()),
                ],
            ],
            'swap' => [
                'bytes' => [
                    'total' => $memoryStats->swapTotal,
                    'free' => $memoryStats->swapFree,
                    'used' => $memoryStats->getUsedSwap(),
                ],
                'readable' => [
                    'total' => Quota::getReadableSize($memoryStats->swapTotal),
                    'free' => Quota::getReadableSize($memoryStats->swapFree),
                    'used' => Quota::getReadableSize($memoryStats->getUsedSwap()),
                ],
            ],
            'disk' => [
                'bytes' => [
                    'total' => $spaceTotal,
                    'free' => $spaceFree,
                    'used' => $spaceUsed,
                ],
                'readable' => [
                    'total' => Quota::getReadableSize($spaceTotal),
                    'free' => Quota::getReadableSize($spaceFree),
                    'used' => Quota::getReadableSize($spaceUsed),
                ],
            ],
            'network' => $networkInterfaces,
        ];

        return $response->withJson($stats);
    }
}
