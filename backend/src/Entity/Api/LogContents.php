<?php

declare(strict_types=1);

namespace App\Entity\Api;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_LogContents',
    required: ['*'],
    type: 'object',
    readOnly: true
)]
final class LogContents
{
    public function __construct(
        #[OA\Property]
        public string $contents,
        #[OA\Property(
            description: 'Whether the log file has ended at this point or has additional data.',
        )]
        public bool $eof,
        #[OA\Property]
        public ?int $position = null,
    ) {
    }
}
