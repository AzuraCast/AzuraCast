<?php

declare(strict_types=1);

namespace App\Entity\Api;

use App\Enums\FlashLevels;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_ToastNotification',
    required: ['*'],
    type: 'object'
)]
final class ToastNotification
{
    public function __construct(
        #[OA\Property]
        public string $message,
        #[OA\Property]
        public ?string $title,
        #[OA\Property]
        public FlashLevels $variant
    ) {
    }
}
