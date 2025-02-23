<?php

declare(strict_types=1);

namespace App\Entity\Api\Admin\ServerStats;

use App\Radio\Quota;
use App\Service\ServerStats\MemoryData;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_Admin_ServerStats_MemoryStats',
    type: 'object'
)]
final class MemoryStats
{
    #[OA\Property]
    public string $total_bytes;

    #[OA\Property]
    public string $total_readable;

    #[OA\Property]
    public string $free_bytes;

    #[OA\Property]
    public string $free_readable;

    #[OA\Property]
    public string $buffers_bytes;

    #[OA\Property]
    public string $buffers_readable;

    #[OA\Property]
    public string $cached_bytes;

    #[OA\Property]
    public string $cached_readable;

    #[OA\Property]
    public string $sReclaimable_bytes;

    #[OA\Property]
    public string $sReclaimable_readable;

    #[OA\Property]
    public string $shmem_bytes;

    #[OA\Property]
    public string $shmem_readable;

    #[OA\Property]
    public string $used_bytes;

    #[OA\Property]
    public string $used_readable;

    public static function fromMemory(MemoryData $memoryStats): self
    {
        $record = new self();

        $record->total_bytes = (string)$memoryStats->memTotal;
        $record->total_readable = Quota::getReadableSize($memoryStats->memTotal, 2);

        $record->free_bytes = (string)$memoryStats->memFree;
        $record->free_readable = Quota::getReadableSize($memoryStats->memFree, 2);

        $record->buffers_bytes = (string)$memoryStats->buffers;
        $record->buffers_readable = Quota::getReadableSize($memoryStats->buffers, 2);

        $record->cached_bytes = (string)$memoryStats->cached;
        $record->cached_readable = Quota::getReadableSize($memoryStats->cached, 2);

        $record->sReclaimable_bytes = (string)$memoryStats->sReclaimable;
        $record->sReclaimable_readable = Quota::getReadableSize($memoryStats->sReclaimable, 2);

        $record->shmem_bytes = (string)$memoryStats->shmem;
        $record->shmem_readable = Quota::getReadableSize($memoryStats->shmem, 2);

        $usedBytes = $memoryStats->getUsedMemory();
        $record->used_bytes = (string)$usedBytes;
        $record->used_readable = Quota::getReadableSize($usedBytes, 2);

        return $record;
    }
}
