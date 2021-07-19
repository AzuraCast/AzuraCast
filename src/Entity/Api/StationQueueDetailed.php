<?php

declare(strict_types=1);

namespace App\Entity\Api;

use App\Entity\Api\Traits\HasLinks;
use App\Traits\LoadFromParentObject;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(type="object", schema="Api_StationQueueDetailed")
 */
class StationQueueDetailed extends StationQueue
{
    use LoadFromParentObject;
    use HasLinks;

    /**
     * Custom AutoDJ playback URI, if it exists.
     *
     * @OA\Property(example="")
     * @var string|null
     */
    public ?string $autodj_custom_uri = null;

    /**
     * Log entries on how the specific queue item was picked by the AutoDJ.
     *
     * @var array|null
     */
    public ?array $log = [];
}
