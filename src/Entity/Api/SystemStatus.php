<?php
namespace App\Entity\Api;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(type="object", schema="Api_SystemStatus")
 */
class SystemStatus
{
    public function __construct()
    {
        $this->online = true;
        $this->timestamp = time();
    }

    /**
     * Whether the service is online or not (should always be true)
     *
     * @OA\Property(example=true)
     * @var boolean
     */
    public $online;

    /**
     * The current UNIX timestamp
     *
     * @OA\Property(example=SAMPLE_TIMESTAMP)
     * @var int
     */
    public $timestamp;
}
