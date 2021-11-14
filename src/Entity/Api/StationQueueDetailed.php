<?php

declare(strict_types=1);

namespace App\Entity\Api;

use App\Entity\Api\NowPlaying\StationQueue;
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
     * Indicates whether the song has been sent to the AutoDJ.
     *
     * @OA\Property
     * @var bool
     */
    public bool $sent_to_autodj = false;

    /**
     * Indicates whether the song has already been marked as played.
     *
     * @OA\Property
     * @var bool
     */
    public bool $is_played = false;

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
