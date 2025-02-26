<?php

declare(strict_types=1);

namespace App\Entity\Api\Admin\ServerStats;

use App\Service\ServerStats\CpuData;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_Admin_ServerStats_CpuStatsSection',
    required: ['*'],
    type: 'object'
)]
final readonly class CpuStatsSection
{
    public function __construct(
        #[OA\Property]
        public string $name,
        #[OA\Property]
        public string $usage,
        #[OA\Property]
        public string $idle,
        #[OA\Property]
        public string $io_wait,
        #[OA\Property]
        public string $steal
    ) {
    }

    public static function fromCpuData(CpuData $cpuData): self
    {
        return new self(
            $cpuData->name,
            (string)$cpuData->getUsage(),
            (string)$cpuData->getIdle(),
            (string)$cpuData->getIoWait(),
            (string)$cpuData->getSteal()
        );
    }
}
