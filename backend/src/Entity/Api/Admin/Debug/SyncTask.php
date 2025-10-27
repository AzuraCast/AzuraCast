<?php

declare(strict_types=1);

namespace App\Entity\Api\Admin\Debug;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_Admin_Debug_SyncTask',
    required: ['*'],
    type: 'object'
)]
final readonly class SyncTask
{
    public function __construct(
        #[OA\Property]
        public string $task,
        #[OA\Property]
        public ?string $pattern,
        #[OA\Property]
        public int $time,
        #[OA\Property]
        public ?int $nextRun,
        #[OA\Property]
        public string $url
    ) {
    }
}
