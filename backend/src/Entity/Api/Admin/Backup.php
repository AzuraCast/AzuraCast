<?php

declare(strict_types=1);

namespace App\Entity\Api\Admin;

use App\Entity\Api\Traits\HasLinks;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_Admin_Backup',
    required: ['*'],
    type: 'object'
)]
final class Backup
{
    use HasLinks;

    public function __construct(
        #[OA\Property]
        public readonly string $path,
        #[OA\Property]
        public readonly string $basename,
        #[OA\Property]
        public readonly string $pathEncoded,
        #[OA\Property]
        public readonly int $timestamp,
        #[OA\Property]
        public readonly int $size,
        #[OA\Property]
        public readonly int $storageLocationId
    ) {
    }
}
