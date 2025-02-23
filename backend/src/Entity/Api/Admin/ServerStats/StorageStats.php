<?php

declare(strict_types=1);

namespace App\Entity\Api\Admin\ServerStats;

use App\Radio\Quota;
use Brick\Math\BigInteger;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_Admin_ServerStats_StorageStats',
    type: 'object'
)]
final class StorageStats
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
    public string $used_bytes;

    #[OA\Property]
    public string $used_readable;

    public static function fromStorage(
        BigInteger $total,
        BigInteger $free,
        BigInteger $used
    ): self {
        $record = new self();
        $record->total_bytes = (string)$total;
        $record->total_readable = Quota::getReadableSize($total, 2);
        $record->free_bytes = (string)$free;
        $record->free_readable = Quota::getReadableSize($free, 2);
        $record->used_bytes = (string)$used;
        $record->used_readable = Quota::getReadableSize($used, 2);

        return $record;
    }
}
