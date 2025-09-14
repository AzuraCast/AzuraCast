<?php

declare(strict_types=1);

namespace App\Entity\Api\Admin\Vue;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_Admin_Vue_SettingsProps',
    required: ['*'],
    type: 'object'
)]
final readonly class SettingsProps
{
    public function __construct(
        #[OA\Property]
        public string $releaseChannel
    ) {
    }
}
