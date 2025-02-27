<?php

declare(strict_types=1);

namespace App\Entity\Api;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_LogContents',
    required: ['*'],
    type: 'object'
)]
final class LogContents
{
    public function __construct(
        #[OA\Property(readOnly: true)]
        public string $contents,
        #[OA\Property(
            description: 'Whether the log file has ended at this point or has additional data.',
            readOnly: true
        )]
        public bool $eof,
        #[OA\Property(
            readOnly: true
        )]
        public ?int $position = null,
    ) {
    }
}
