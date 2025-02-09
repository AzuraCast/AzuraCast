<?php

declare(strict_types=1);

namespace App\Entity\Api;

use App\Entity\StorageLocation;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_StationQuota',
    type: 'object'
)]
final class StationQuota
{
    #[OA\Property]
    public string $used;

    #[OA\Property]
    public string $used_bytes;

    #[OA\Property]
    public int $used_percent;

    #[OA\Property]
    public string $available;

    #[OA\Property]
    public string $available_bytes;

    #[OA\Property]
    public string|null $quota;

    #[OA\Property]
    public string|null $quota_bytes;

    #[OA\Property]
    public bool $is_full;

    #[OA\Property]
    public ?int $num_files = null;

    public static function fromStorageLocation(
        StorageLocation $storageLocation,
        int|null $numFiles = null
    ): self {
        $record = new self();
        $record->used = $storageLocation->getStorageUsed();
        $record->used_bytes = (string)$storageLocation->getStorageUsedBytes();
        $record->used_percent = $storageLocation->getStorageUsePercentage();
        $record->available = $storageLocation->getStorageAvailable();
        $record->available_bytes = (string)$storageLocation->getStorageAvailableBytes();
        $record->quota = $storageLocation->getStorageQuota();
        $record->quota_bytes = (string)$storageLocation->getStorageQuotaBytes();
        $record->is_full = $storageLocation->isStorageFull();
        $record->num_files = $numFiles;

        return $record;
    }
}
