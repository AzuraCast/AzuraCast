<?php

declare(strict_types=1);

namespace App\Entity\Api;

use Carbon\CarbonImmutable;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(type="object", schema="Api_Time")
 */
class Time
{
    /**
     * The current UNIX timestamp
     *
     * @OA\Property(example=1497652397)
     * @var int
     */
    public $timestamp;

    /**
     * @OA\Property(example="2017-06-16 10:33:17")
     * @var string
     */
    public string $utc_datetime;

    /**
     * @OA\Property(example="June 16, 2017")
     * @var string
     */
    public string $utc_date;

    /**
     * @OA\Property(example="10:33pm")
     * @var string
     */
    public string $utc_time;

    /**
     * @OA\Property(example="2012-12-25T16:30:00.000000Z")
     * @var string
     */
    public string $utc_json;

    public function __construct()
    {
        $now = CarbonImmutable::now('UTC');

        $this->timestamp = $now->getTimestamp();
        $this->utc_datetime = $now->format('Y-m-d g:i:s');
        $this->utc_date = $now->format('F j, Y');
        $this->utc_time = $now->format('g:ia');
        $this->utc_json = $now->toJSON() ?? '';
    }
}
