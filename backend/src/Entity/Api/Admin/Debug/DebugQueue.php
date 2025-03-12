<?php

declare(strict_types=1);

namespace App\Entity\Api\Admin\Debug;

use App\MessageQueue\QueueNames;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_Admin_Debug_Queue',
    required: ['*'],
    type: 'object'
)]
final readonly class DebugQueue
{
    public function __construct(
        #[OA\Property(enum: QueueNames::class)]
        public string $name,
        #[OA\Property]
        public int $count,
        #[OA\Property]
        public string $url
    ) {
    }
}
