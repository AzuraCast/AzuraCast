<?php

declare(strict_types=1);

namespace App\Entity\Api\Admin\ServerStats;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_Admin_ServerStats',
    required: ['*'],
    type: 'object'
)]
final readonly class ServerStats
{
    public function __construct(
        #[OA\Property]
        public CpuStats $cpu,
        #[OA\Property]
        public MemoryStats $memory,
        #[OA\Property]
        public StorageStats $swap,
        #[OA\Property]
        public StorageStats $disk,
        #[OA\Property(
            items: new OA\Items(ref: '#/components/schemas/Api_Admin_ServerStats_NetworkInterfaceStats')
        )]
        public array $network
    ) {
    }
}
