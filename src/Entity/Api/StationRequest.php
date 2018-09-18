<?php

namespace App\Entity\Api;

use App\Entity;

/**
 * @OA\Schema(type="object")
 */
class StationRequest
{
    /**
     * Requestable ID unique identifier
     *
     * @OA\Property(example=1)
     * @var int
     */
    public $request_id;

    /**
     * URL to directly submit request
     *
     * @OA\Property(example="/api/station/1/request/1")
     * @var int
     */
    public $request_url;

    /**
     * Song
     *
     * @OA\Property
     * @var Song
     */
    public $song;
}
