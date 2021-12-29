<?php

declare(strict_types=1);

namespace App\Entity\Api;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_SystemStatus',
    type: 'object'
)]
class SystemStatus
{
    #[OA\Property(
        description: 'Whether the service is online or not (should always be true)',
        example: true
    )]
    public bool $online = true;

    #[OA\Property(
        description: 'The current UNIX timestamp',
        example: 1609480800
    )]
    public int $timestamp;

    public function __construct()
    {
        $this->timestamp = time();
    }
}
