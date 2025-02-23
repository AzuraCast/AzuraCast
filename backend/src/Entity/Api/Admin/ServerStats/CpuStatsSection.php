<?php

declare(strict_types=1);

namespace App\Entity\Api\Admin\ServerStats;

use App\Service\ServerStats\CpuData;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_Admin_ServerStats_CpuStatsSection',
    type: 'object'
)]
final class CpuStatsSection
{
    #[OA\Property]
    public string $name;

    #[OA\Property]
    public string $usage;

    #[OA\Property]
    public string $idle;

    #[OA\Property]
    public string $io_wait;

    #[OA\Property]
    public string $steal;

    public static function fromCpuData(CpuData $cpuData): self
    {
        $return = new self();
        $return->name = $cpuData->name;
        $return->usage = (string)$cpuData->getUsage();
        $return->idle = (string)$cpuData->getIdle();
        $return->io_wait = (string)$cpuData->getIoWait();
        $return->steal = (string)$cpuData->getSteal();

        return $return;
    }
}
