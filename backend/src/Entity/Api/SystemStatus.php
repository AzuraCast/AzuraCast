<?php

declare(strict_types=1);

namespace App\Entity\Api;

use App\OpenApi;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_SystemStatus',
    required: ['*'],
    type: 'object'
)]
final readonly class SystemStatus
{
    #[OA\Property(
        description: 'Whether the service is online or not (should always be true)',
        example: true
    )]
    public bool $online;

    #[OA\Property(
        description: 'The current UNIX timestamp',
        example: OpenApi::SAMPLE_TIMESTAMP
    )]
    public int $timestamp;

    public function __construct()
    {
        $this->online = true;
        $this->timestamp = time();
    }
}
