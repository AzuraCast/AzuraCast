<?php

declare(strict_types=1);

namespace App\Entity\Api;

use App\Entity\Api\Traits\HasLinks;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_LogType',
    required: ['*'],
    type: 'object'
)]
final class LogType
{
    use HasLinks;

    public function __construct(
        #[OA\Property(readOnly: true)]
        public string $key,
        #[OA\Property(readOnly: true)]
        public string $name,
        #[OA\Property(readOnly: true)]
        public string $path,
        #[OA\Property(readOnly: true)]
        public bool $tail = false
    ) {
    }
}
