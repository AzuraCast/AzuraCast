<?php

declare(strict_types=1);

namespace App\Entity\Api\Admin\Vue;

use App\Entity\Api\Admin\UpdateDetails;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_Admin_Vue_UpdateProps',
    required: ['*'],
    type: 'object'
)]
final readonly class UpdateProps
{
    public function __construct(
        #[OA\Property]
        public string $releaseChannel,
        #[OA\Property]
        public bool $enableWebUpdates,
        #[OA\Property]
        public ?UpdateDetails $initialUpdateInfo,
    ) {
    }
}
