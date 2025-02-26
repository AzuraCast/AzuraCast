<?php

declare(strict_types=1);

namespace App\Entity\Api\Admin\ServerStats;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_Admin_ServerStats_CpuStats',
    required: ['*'],
    type: 'object'
)]
final class CpuStats
{
    public function __construct(
        #[OA\Property]
        public CpuStatsSection $total,
        #[OA\Property(items: new OA\Items(ref: '#/components/schemas/Api_Admin_ServerStats_CpuStatsSection'))]
        public array $cores,
        #[OA\Property(items: new OA\Items(type: 'integer', format: 'int64'))]
        public array $load
    ) {
    }
}
