<?php

namespace App\Entity\Api;

use App\Entity;

/**
 * @SWG\Definition(type="object")
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
     * @SWG\Property(example=true)
     * @var boolean
     */
    public $online;

    /**
     * The current UNIX timestamp
     *
     * @SWG\Property(example=SAMPLE_TIMESTAMP)
     * @var int
     */
    public $timestamp;
}
