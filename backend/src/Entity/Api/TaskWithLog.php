<?php

declare(strict_types=1);

namespace App\Entity\Api;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_TaskWithLog',
    required: ['*'],
    type: 'object'
)]
final readonly class TaskWithLog
{
    public function __construct(
        #[OA\Property(
            description: 'The URL to view logs of the ongoing background task.',
            format: 'uri'
        )]
        public string $logUrl
    ) {
    }
}
