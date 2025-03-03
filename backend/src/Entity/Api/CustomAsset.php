<?php

declare(strict_types=1);

namespace App\Entity\Api;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_CustomAsset',
    required: ['*'],
    type: 'object'
)]
final readonly class CustomAsset extends AbstractStatus
{
    public function __construct(
        #[OA\Property]
        public bool $is_uploaded,
        #[OA\Property]
        public string $url
    ) {
        parent::__construct(true);
    }
}
