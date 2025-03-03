<?php

declare(strict_types=1);

namespace App\Entity\Api;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_AbstractStatus',
    required: ['*'],
    type: 'object'
)]
abstract readonly class AbstractStatus
{
    public function __construct(
        #[OA\Property(example: true)]
        public bool $success
    ) {
    }
}
