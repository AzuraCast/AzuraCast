<?php

declare(strict_types=1);

namespace App\Entity\Api\Admin\ServerStats;

use App\Radio\Quota;
use Brick\Math\BigInteger;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_Admin_ServerStats_StorageStats',
    required: ['*'],
    type: 'object'
)]
final class StorageStats
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
        public string $used_bytes,
        #[OA\Property]
        public string $used_readable,
    ) {
    }

    public static function fromStorage(
        BigInteger $total,
        BigInteger $free,
        BigInteger $used
    ): self {
        return new self(
            (string)$total,
            Quota::getReadableSize($total, 2),
            (string)$free,
            Quota::getReadableSize($free, 2),
            (string)$used,
            Quota::getReadableSize($used, 2)
        );
    }
}
