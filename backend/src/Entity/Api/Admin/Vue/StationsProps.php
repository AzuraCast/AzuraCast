<?php

declare(strict_types=1);

namespace App\Entity\Api\Admin\Vue;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_Admin_Vue_StationsProps',
    required: ['*'],
    type: 'object'
)]
final readonly class StationsProps
{
    public function __construct(
        #[OA\Property]
        public StationsFormProps $formProps,
        #[OA\Property(type: 'object')]
        public array $frontendTypes,
        #[OA\Property(type: 'object')]
        public array $backendTypes,
    ) {
    }
}
