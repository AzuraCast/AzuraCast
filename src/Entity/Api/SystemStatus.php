<?php

declare(strict_types=1);

namespace App\Entity\Api;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(type="object", schema="Api_SystemStatus")
 */
class SystemStatus
{
    /**
     * Whether the service is online or not (should always be true)
     *
     * @OA\Property(example=true)
     * @var bool
     */
    public bool $online = true;

    /**
     * The current UNIX timestamp
     *
     * @OA\Property(example=SAMPLE_TIMESTAMP)
     * @var int
     */
    public $timestamp;

    public function __construct()
    {
        $this->timestamp = time();
    }
}
