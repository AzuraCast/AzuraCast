<?php

declare(strict_types=1);

namespace App\Entity\Api;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(type="object", schema="Api_StationServiceStatus")
 */
class StationServiceStatus
{
    /**
     * @OA\Property(example=true)
     * @var bool
     */
    public bool $backend_running;

    /**
     * @OA\Property(example=true)
     * @var bool
     */
    public bool $frontend_running;

    public function __construct(bool $backend_running, bool $frontend_running)
    {
        $this->backend_running = $backend_running;
        $this->frontend_running = $frontend_running;
    }
}
