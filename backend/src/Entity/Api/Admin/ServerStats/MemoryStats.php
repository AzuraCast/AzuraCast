<?php

declare(strict_types=1);

namespace App\Entity\Api\Admin\ServerStats;

use App\Radio\Quota;
use App\Service\ServerStats\MemoryData;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_Admin_ServerStats_MemoryStats',
    required: ['*'],
    type: 'object'
)]
final readonly class MemoryStats
{
    public function __construct(
        #[OA\Property]
        public string $total_bytes,
        #[OA\Property]
        public string $total_readable,
        #[OA\Property]
        public string $free_bytes,
        #[OA\Property]
        public string $free_readable,
        #[OA\Property]
        public string $buffers_bytes,
        #[OA\Property]
        public string $buffers_readable,
        #[OA\Property]
        public string $cached_bytes,
        #[OA\Property]
        public string $cached_readable,
        #[OA\Property]
        public string $sReclaimable_bytes,
        #[OA\Property]
        public string $sReclaimable_readable,
        #[OA\Property]
        public string $shmem_bytes,
        #[OA\Property]
        public string $shmem_readable,
        #[OA\Property]
        public string $used_bytes,
        #[OA\Property]
        public string $used_readable
    ) {
    }

    public static function fromMemory(MemoryData $memoryStats): self
    {
        $usedBytes = $memoryStats->getUsedMemory();

        return new self(
            (string)$memoryStats->memTotal,
            Quota::getReadableSize($memoryStats->memTotal, 2),
            (string)$memoryStats->memFree,
            Quota::getReadableSize($memoryStats->memFree, 2),
            (string)$memoryStats->buffers,
            Quota::getReadableSize($memoryStats->buffers, 2),
            (string)$memoryStats->cached,
            Quota::getReadableSize($memoryStats->cached, 2),
            (string)$memoryStats->sReclaimable,
            Quota::getReadableSize($memoryStats->sReclaimable, 2),
            (string)$memoryStats->shmem,
            Quota::getReadableSize($memoryStats->shmem, 2),
            (string)$usedBytes,
            Quota::getReadableSize($usedBytes, 2)
        );
    }
}
