<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin;

use App\Container\EnvironmentAwareTrait;
use App\Controller\SingleActionInterface;
use App\Entity\Api\Admin\ServerStats\CpuStats as ApiCpuStats;
use App\Entity\Api\Admin\ServerStats\CpuStatsSection;
use App\Entity\Api\Admin\ServerStats\MemoryStats as ApiMemoryStats;
use App\Entity\Api\Admin\ServerStats\NetworkInterfaceReceived;
use App\Entity\Api\Admin\ServerStats\NetworkInterfaceStats;
use App\Entity\Api\Admin\ServerStats\NetworkInterfaceTransmitted;
use App\Entity\Api\Admin\ServerStats\ServerStats as ApiServerStats;
use App\Entity\Api\Admin\ServerStats\StorageStats;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use App\Service\ServerStats;
use Brick\Math\BigInteger;
use Brick\Math\RoundingMode;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[
    OA\Get(
        path: '/admin/server/stats',
        operationId: 'getServerStats',
        description: 'Return a list of all CPU usage stats.',
        security: OpenApi::API_KEY_SECURITY,
        tags: ['Administration: CPU stats'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(ref: '#/components/schemas/Api_Admin_ServerStats')
            ),
            new OA\Response(ref: OpenApi::REF_RESPONSE_ACCESS_DENIED, response: 403),
            new OA\Response(ref: OpenApi::REF_RESPONSE_NOT_FOUND, response: 404),
            new OA\Response(ref: OpenApi::REF_RESPONSE_GENERIC_ERROR, response: 500),
        ]
    )
]
final class ServerStatsAction implements SingleActionInterface
{
    use EnvironmentAwareTrait;

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $firstCpuMeasurement = ServerStats::getCurrentLoad();
        $firstNetworkMeasurement = ServerStats::getNetworkUsage();

        $measurementTime = 1;
        sleep($measurementTime);

        $secondCpuMeasurement = ServerStats::getCurrentLoad();
        $secondNetworkMeasurement = ServerStats::getNetworkUsage();

        /** @var CpuStatsSection|null $cpuTotal */
        $cpuTotal = null;

        /** @var CpuStatsSection[] $statsPerCore */
        $statsPerCore = [];

        foreach ($secondCpuMeasurement as $index => $currentCoreData) {
            $previousCpuData = $firstCpuMeasurement[$index];
            $deltaCpuData = ServerStats::calculateCpuDelta($currentCoreData, $previousCpuData);
            $cpuStats = CpuStatsSection::fromCpuData($deltaCpuData);

            if ($deltaCpuData->name === 'total') {
                $cpuTotal = $cpuStats;
            } else {
                $statsPerCore[] = $cpuStats;
            }
        }

        assert($cpuTotal !== null);

        /** @var NetworkInterfaceStats[] $networkInterfaces */
        $networkInterfaces = [];

        foreach ($secondNetworkMeasurement as $index => $currentNetworkMeasurement) {
            $previousNetworkMeasurement = $firstNetworkMeasurement[$index];
            $deltaNetworkData = ServerStats::calculateNetworkDelta(
                $currentNetworkMeasurement,
                $previousNetworkMeasurement
            );

            $bytesPerTimeReceived = $deltaNetworkData->received->bytes
                ->dividedBy($measurementTime, RoundingMode::HALF_UP)
                ->toBigInteger();

            $bytesPerTimeTransmitted = $deltaNetworkData->transmitted->bytes
                ->dividedBy($measurementTime, RoundingMode::HALF_UP)
                ->toBigInteger();

            $networkInterfaces[] = new NetworkInterfaceStats(
                $deltaNetworkData->interfaceName,
                NetworkInterfaceReceived::fromReceived(
                    $deltaNetworkData->received,
                    $bytesPerTimeReceived
                ),
                NetworkInterfaceTransmitted::fromTransmitted(
                    $deltaNetworkData->transmitted,
                    $bytesPerTimeTransmitted
                )
            );
        }

        $memoryStats = ServerStats::getMemoryUsage();

        $spaceTotalFloat = disk_total_space($this->environment->getStationDirectory());
        $spaceTotal = (is_float($spaceTotalFloat))
            ? BigInteger::of($spaceTotalFloat)
            : BigInteger::zero();

        $spaceFreeFloat = disk_free_space($this->environment->getStationDirectory());
        $spaceFree = (is_float($spaceFreeFloat))
            ? BigInteger::of($spaceFreeFloat)
            : BigInteger::zero();

        $spaceUsed = $spaceTotal->minus($spaceFree);

        return $response->withJson(
            new ApiServerStats(
                new ApiCpuStats(
                    $cpuTotal,
                    $statsPerCore,
                    sys_getloadavg() ?: [0, 0, 0]
                ),
                ApiMemoryStats::fromMemory($memoryStats),
                StorageStats::fromStorage(
                    $memoryStats->swapTotal,
                    $memoryStats->swapFree,
                    $memoryStats->getUsedSwap(),
                ),
                StorageStats::fromStorage(
                    $spaceTotal,
                    $spaceFree,
                    $spaceUsed
                ),
                $networkInterfaces,
            )
        );
    }
}
