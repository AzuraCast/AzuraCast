<?php

declare(strict_types=1);

namespace App\Entity\Api;

use App\Entity\Api\Traits\HasLinks;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_LogType',
    required: ['*'],
    type: 'object',
    readOnly: true
)]
final class LogType
{
    use HasLinks;

    public function __construct(
        #[OA\Property]
        public string $key,
        #[OA\Property]
        public string $name,
        #[OA\Property]
        public string $path,
        #[OA\Property]
        public bool $tail = false
    ) {
    }
}
