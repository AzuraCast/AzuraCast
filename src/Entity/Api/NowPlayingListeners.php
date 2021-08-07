<?php

declare(strict_types=1);

namespace App\Entity\Api;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(type="object", schema="Api_NowPlayingListeners")
 */
class NowPlayingListeners
{
    /**
     * Total non-unique current listeners
     * @OA\Property(example=20)
     * @var int
     */
    public int $total = 0;

    /**
     * Total unique current listeners
     * @OA\Property(example=15)
     * @var int
     */
    public int $unique = 0;

    /**
     * Total non-unique current listeners (Legacy field, may be retired in the future.)
     * @OA\Property(example=20)
     * @var int
     */
    public int $current = 0;

    public function __construct(
        int $total = 0,
        ?int $unique = null
    ) {
        $this->total = $total;
        $this->current = $total;

        $this->unique = $unique ?? 0;
    }
}
