<?php
namespace App\Entity\Api;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(type="object", schema="Api_StationServiceStatus")
 */
class StationServiceStatus
{
    public function __construct($backend_running, $frontend_running)
    {
        $this->backend_running = (bool)$backend_running;
        $this->frontend_running = (bool)$frontend_running;
    }

    /**
     * @OA\Property(example=true)
     * @var bool
     */
    public $backend_running;

    /**
     * @OA\Property(example=true)
     * @var bool
     */
    public $frontend_running;
}
