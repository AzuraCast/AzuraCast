<?php

declare(strict_types=1);

namespace App\Entity\Api\Admin;

use App\Entity\Api\AbstractStatus;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_Admin_RsasStatus',
    required: ['*'],
    type: 'object'
)]
final readonly class RsasStatus extends AbstractStatus
{
    public function __construct(
        #[OA\Property]
        public string|null $version,
        #[OA\Property]
        public bool $hasLicense
    ) {
        parent::__construct(true);
    }
}
