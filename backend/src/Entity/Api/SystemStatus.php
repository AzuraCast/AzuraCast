<?php

declare(strict_types=1);

namespace App\Entity\Api;

use App\OpenApi;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_SystemStatus',
    type: 'object'
)]
final class SystemStatus
{
    #[OA\Property(
        description: 'Whether the service is online or not (should always be true)',
        example: true
    )]
    public bool $online = true;

    #[OA\Property(
        description: 'The current UNIX timestamp',
        example: OpenApi::SAMPLE_TIMESTAMP
    )]
    public int $timestamp;

    public function __construct()
    {
        $this->timestamp = time();
    }
}
