<?php

declare(strict_types=1);

namespace App\Entity\Api\Admin\Vue;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_Admin_Vue_StationsFormProps',
    required: ['*'],
    type: 'object'
)]
final readonly class StationsFormProps
{
    public function __construct(
        #[OA\Property(
            type: 'object',
            additionalProperties: new OA\AdditionalProperties(type: 'string')
        )]
        public array $timezones,
        #[OA\Property(
            type: 'object',
            additionalProperties: new OA\AdditionalProperties(type: 'string')
        )]
        public array $countries,
        #[OA\Property]
        public bool $isRsasInstalled,
        #[OA\Property]
        public bool $isShoutcastInstalled,
        #[OA\Property]
        public bool $isStereoToolInstalled
    ) {
    }
}
