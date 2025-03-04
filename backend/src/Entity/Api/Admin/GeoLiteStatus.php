<?php

declare(strict_types=1);

namespace App\Entity\Api\Admin;

use App\Entity\Api\AbstractStatus;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_Admin_GeoLiteStatus',
    required: ['*'],
    type: 'object'
)]
final readonly class GeoLiteStatus extends AbstractStatus
{
    public function __construct(
        #[OA\Property]
        public string|null $version,
        #[OA\Property]
        public string|null $key,
    ) {
        parent::__construct(true);
    }
}
