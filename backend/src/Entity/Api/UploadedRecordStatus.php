<?php

declare(strict_types=1);

namespace App\Entity\Api;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_UploadedRecordStatus',
    required: ['*'],
    type: 'object'
)]
final readonly class UploadedRecordStatus extends AbstractStatus
{
    public function __construct(
        #[OA\Property]
        public bool $hasRecord,
        #[OA\Property]
        public ?string $url = null
    ) {
        parent::__construct(true);
    }
}
