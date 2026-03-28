<?php

declare(strict_types=1);

namespace App\Entity\Api;

use App\Enums\FlashLevels;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_Notification',
    required: ['*'],
    type: 'object'
)]
final readonly class Notification
{
    public function __construct(
        #[OA\Property]
        public string $id,
        #[OA\Property]
        public string $title,
        #[OA\Property]
        public string $body,
        #[OA\Property]
        public FlashLevels $type,
        #[OA\Property]
        public ?string $actionLabel = null,
        #[OA\Property]
        public ?string $actionUrl = null
    ) {
    }
}
