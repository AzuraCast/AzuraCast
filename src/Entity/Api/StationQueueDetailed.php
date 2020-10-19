<?php

namespace App\Entity\Api;

use App\Traits\LoadFromParentObject;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(type="object", schema="Api_StationQueueDetailed")
 */
class StationQueueDetailed extends StationQueue
{
    use LoadFromParentObject;

    /**
     * Custom AutoDJ playback URI, if it exists.
     *
     * @OA\Property(example="")
     * @var string|null
     */
    public ?string $autodj_custom_uri = null;

    /**
     * @OA\Property(
     *     @OA\Items(
     *         type="string",
     *         example="http://localhost/api/stations/1/queue/1"
     *     )
     * )
     * @var array
     */
    public array $links = [];
}
