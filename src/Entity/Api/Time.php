<?php

declare(strict_types=1);

namespace App\Entity\Api;

use Carbon\CarbonImmutable;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_Time',
    type: 'object'
)]
final class Time
{
    #[OA\Property(
        description: 'The current UNIX timestamp',
        example: 1497652397
    )]
    public int $timestamp;

    #[OA\Property(example: '2017-06-16 10:33:17')]
    public string $utc_datetime;

    #[OA\Property(example: 'June 16, 2017')]
    public string $utc_date;

    #[OA\Property(example: '10:33pm')]
    public string $utc_time;

    #[OA\Property(example: '2012-12-25T16:30:00.000000Z')]
    public string $utc_json;

    public function __construct()
    {
        $now = CarbonImmutable::now('UTC');

        $this->timestamp = $now->getTimestamp();
        $this->utc_datetime = $now->format('Y-m-d g:i:s');
        $this->utc_date = $now->format('F j, Y');
        $this->utc_time = $now->format('g:ia');
        $this->utc_json = $now->toJSON();
    }
}
